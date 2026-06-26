<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonConnecte();

$pdo = getPDO();
$idClient = $_SESSION['user_id'];
$idCommande = (int) ($_GET['id'] ?? 0);

// Vérifier que la commande appartient bien au client connecté
$stmt = $pdo->prepare("SELECT idCommande, dateCommande, montantTotal, statut FROM commandes WHERE idCommande = ? AND idClient = ?");
$stmt->execute([$idCommande, $idClient]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    flashMessage('danger', 'Commande introuvable.');
    rediriger(URL_BASE . '/commandes/historique.php');
}

if ($commande['statut'] === 'en attente') {
    // Paiement pas encore effectué : on ne montre pas une confirmation
    rediriger(URL_BASE . '/commandes/paiement.php?id=' . $idCommande);
}

// Récupérer les lignes de la commande
$stmt = $pdo->prepare("SELECT lc.quantite, lc.prixUnitaire, p.nom, p.image
                        FROM lignes_commande lc
                        JOIN produits p ON lc.idProduit = p.idProduit
                        WHERE lc.idCommande = ?");
$stmt->execute([$idCommande]);
$lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="alert alert-success text-center">
        <h1 class="h3 mb-2"><i class="bi bi-check-circle-fill"></i> Commande confirmée !</h1>
        <p class="mb-0">Merci pour votre achat. Votre numéro de commande est <strong>#<?= (int) $commande['idCommande'] ?></strong>.</p>
    </div>

    <h2 class="h5 mt-4">Récapitulatif</h2>
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
                        <td><?= formaterPrix($ligne['prixUnitaire']) ?></td>
                        <td><?= (int) $ligne['quantite'] ?></td>
                        <td><strong><?= formaterPrix($ligne['prixUnitaire'] * $ligne['quantite']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total payé</th>
                    <th><?= formaterPrix($commande['montantTotal']) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="text-center mt-4">
        <a href="<?= URL_BASE ?>/commandes/suivi.php?id=<?= (int) $commande['idCommande'] ?>" class="btn btn-outline-secondary">
            Suivre cette commande
        </a>
        <a href="<?= URL_BASE ?>/produits/liste.php" class="btn btn-primary">
            Continuer mes achats
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
