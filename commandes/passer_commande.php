<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonConnecte();

$pdo = getPDO();
$idClient = $_SESSION['user_id'];

// Récupérer le panier du client
$stmt = $pdo->prepare("SELECT idPanier FROM paniers WHERE idClient = ?");
$stmt->execute([$idClient]);
$panier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$panier) {
    flashMessage('info', 'Votre panier est vide.');
    rediriger(URL_BASE . '/panier/panier.php');
}

$idPanier = (int) $panier['idPanier'];

// --- Traitement de la confirmation de commande (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger', 'Requête invalide.');
        rediriger(URL_BASE . '/panier/panier.php');
    }

    try {
        $pdo->beginTransaction();

        // Relire les lignes du panier avec verrou et infos produit/promo à jour
        $sql = "SELECT lp.idLignePanier, lp.idProduit, lp.quantite, p.nom, p.prix, p.stock,
                       pr.typeReduction, pr.valeur
                FROM lignes_panier lp
                JOIN produits p ON lp.idProduit = p.idProduit
                LEFT JOIN promotions pr ON pr.idProduit = p.idProduit
                       AND pr.actif = 1
                       AND CURDATE() BETWEEN pr.dateDebut AND pr.dateFin
                WHERE lp.idPanier = ?
                FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idPanier]);
        $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($lignes)) {
            $pdo->rollBack();
            flashMessage('info', 'Votre panier est vide.');
            rediriger(URL_BASE . '/panier/panier.php');
        }

        // Vérifier le stock disponible pour chaque ligne avant toute écriture
        foreach ($lignes as $ligne) {
            if ($ligne['quantite'] > $ligne['stock']) {
                $pdo->rollBack();
                flashMessage('warning', 'Stock insuffisant pour "' . $ligne['nom'] . '". Merci d\'ajuster votre panier.');
                rediriger(URL_BASE . '/panier/panier.php');
            }
        }

        // Calculer le montant total en tenant compte des promotions actives
        $montantTotal = 0;
        foreach ($lignes as &$ligne) {
            $prixUnitaire = (float) $ligne['prix'];
            if ($ligne['typeReduction'] === 'pourcentage') {
                $prixUnitaire = $prixUnitaire - ($prixUnitaire * (float) $ligne['valeur'] / 100);
            } elseif ($ligne['typeReduction'] === 'montant') {
                $prixUnitaire = max(0, $prixUnitaire - (float) $ligne['valeur']);
            }
            $ligne['prixUnitaireFinal'] = $prixUnitaire;
            $montantTotal += $prixUnitaire * $ligne['quantite'];
        }
        unset($ligne);

        // Créer la commande
        $stmt = $pdo->prepare("INSERT INTO commandes (idClient, dateCommande, montantTotal, statut)
                                VALUES (?, NOW(), ?, 'en attente')");
        $stmt->execute([$idClient, $montantTotal]);
        $idCommande = (int) $pdo->lastInsertId();

        // Copier les lignes du panier vers lignes_commande + décrémenter le stock
        $insertLigne = $pdo->prepare("INSERT INTO lignes_commande (idCommande, idProduit, quantite, prixUnitaire)
                                       VALUES (?, ?, ?, ?)");
        $decrementerStock = $pdo->prepare("UPDATE produits SET stock = stock - ? WHERE idProduit = ?");

        foreach ($lignes as $ligne) {
            $insertLigne->execute([$idCommande, $ligne['idProduit'], $ligne['quantite'], $ligne['prixUnitaireFinal']]);
            $decrementerStock->execute([$ligne['quantite'], $ligne['idProduit']]);
        }

        // Vider le panier
        $pdo->prepare("DELETE FROM lignes_panier WHERE idPanier = ?")->execute([$idPanier]);
        $pdo->prepare("UPDATE paniers SET total = 0, dateMaj = NOW() WHERE idPanier = ?")->execute([$idPanier]);

        $pdo->commit();

        rediriger(URL_BASE . '/commandes/paiement.php?id=' . $idCommande);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flashMessage('danger', 'Une erreur est survenue lors de la création de la commande. Merci de réessayer.');
        rediriger(URL_BASE . '/panier/panier.php');
    }
}

// --- Affichage du récapitulatif avant confirmation (GET) ---
$sql = "SELECT lp.idLignePanier, lp.idProduit, lp.quantite, p.nom, p.image, p.prix, p.stock,
               pr.typeReduction, pr.valeur
        FROM lignes_panier lp
        JOIN produits p ON lp.idProduit = p.idProduit
        LEFT JOIN promotions pr ON pr.idProduit = p.idProduit
               AND pr.actif = 1
               AND CURDATE() BETWEEN pr.dateDebut AND pr.dateFin
        WHERE lp.idPanier = ?
        ORDER BY lp.idLignePanier ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$idPanier]);
$lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($lignes)) {
    flashMessage('info', 'Votre panier est vide.');
    rediriger(URL_BASE . '/panier/panier.php');
}

$totalGeneral = 0;
foreach ($lignes as &$ligne) {
    $prixUnitaire = (float) $ligne['prix'];
    if ($ligne['typeReduction'] === 'pourcentage') {
        $prixUnitaire = $prixUnitaire - ($prixUnitaire * (float) $ligne['valeur'] / 100);
    } elseif ($ligne['typeReduction'] === 'montant') {
        $prixUnitaire = max(0, $prixUnitaire - (float) $ligne['valeur']);
    }
    $ligne['prixUnitaireFinal'] = $prixUnitaire;
    $ligne['sousTotal'] = $prixUnitaire * $ligne['quantite'];
    $totalGeneral += $ligne['sousTotal'];
}
unset($ligne);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Récapitulatif de la commande</h1>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Prix unitaire</th>
                    <th>Quantité</th>
                    <th>Sous-total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= URL_PRODUITS . h($ligne['image']) ?>" alt="<?= h($ligne['nom']) ?>"
                                     style="width:50px;height:50px;object-fit:cover;border-radius:4px;">
                                <?= h($ligne['nom']) ?>
                            </div>
                        </td>
                        <td><?= formaterPrix($ligne['prixUnitaireFinal']) ?></td>
                        <td><?= (int) $ligne['quantite'] ?></td>
                        <td><strong><?= formaterPrix($ligne['sousTotal']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total général</th>
                    <th><?= formaterPrix($totalGeneral) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <form action="<?= URL_BASE ?>/commandes/passer_commande.php" method="post" class="text-end">
        <input type="hidden" name="csrf_token" value="<?= h(genererToken()) ?>">
        <a href="<?= URL_BASE ?>/panier/panier.php" class="btn btn-outline-secondary">Retour au panier</a>
        <button type="submit" class="btn btn-primary btn-lg">Confirmer la commande</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
