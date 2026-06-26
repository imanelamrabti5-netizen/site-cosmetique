<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonConnecte();

$pdo = getPDO();
$idClient = $_SESSION['user_id'];

// Récupérer ou créer le panier du client
$stmt = $pdo->prepare("SELECT idPanier FROM paniers WHERE idClient = ?");
$stmt->execute([$idClient]);
$panier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$panier) {
    $stmt = $pdo->prepare("INSERT INTO paniers (idClient, total, dateMaj) VALUES (?, 0, NOW())");
    $stmt->execute([$idClient]);
    $idPanier = (int) $pdo->lastInsertId();
} else {
    $idPanier = (int) $panier['idPanier'];
}

// Récupérer les lignes du panier avec infos produit + promotion active éventuelle
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

    // Garder le sous-total en base cohérent avec le prix affiché (y compris promo)
    $maj = $pdo->prepare("UPDATE lignes_panier SET sousTotal = ? WHERE idLignePanier = ?");
    $maj->execute([$ligne['sousTotal'], $ligne['idLignePanier']]);
}
unset($ligne);

// Mettre à jour le total du panier en base
$majPanier = $pdo->prepare("UPDATE paniers SET total = ?, dateMaj = NOW() WHERE idPanier = ?");
$majPanier->execute([$totalGeneral, $idPanier]);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Mon panier</h1>

    <?php if (empty($lignes)): ?>
        <div class="alert alert-info">
            Votre panier est vide. <a href="<?= URL_BASE ?>/produits/liste.php">Voir les produits</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Prix unitaire</th>
                        <th>Quantité</th>
                        <th>Sous-total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lignes as $ligne): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= URL_PRODUITS . h($ligne['image']) ?>" alt="<?= h($ligne['nom']) ?>"
                                         style="width:60px;height:60px;object-fit:cover;border-radius:4px;">
                                    <a href="<?= URL_BASE ?>/produits/details.php?id=<?= (int) $ligne['idProduit'] ?>">
                                        <?= h($ligne['nom']) ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <?= formaterPrix($ligne['prixUnitaireFinal']) ?>
                                <?php if ($ligne['typeReduction']): ?>
                                    <br><small class="text-decoration-line-through text-muted"><?= formaterPrix($ligne['prix']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="max-width:150px;">
                                <form action="<?= URL_BASE ?>/panier/modifier.php" method="post" class="d-flex gap-1">
                                    <input type="hidden" name="csrf_token" value="<?= h(genererToken()) ?>">
                                    <input type="hidden" name="idLignePanier" value="<?= (int) $ligne['idLignePanier'] ?>">
                                    <input type="number" name="quantite" value="<?= (int) $ligne['quantite'] ?>"
                                           min="1" max="<?= (int) $ligne['stock'] ?>" class="form-control form-control-sm">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">OK</button>
                                </form>
                            </td>
                            <td><strong><?= formaterPrix($ligne['sousTotal']) ?></strong></td>
                            <td>
                                <form action="<?= URL_BASE ?>/panier/supprimer.php" method="post">
                                    <input type="hidden" name="csrf_token" value="<?= h(genererToken()) ?>">
                                    <input type="hidden" name="idLignePanier" value="<?= (int) $ligne['idLignePanier'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Retirer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total général</th>
                        <th><?= formaterPrix($totalGeneral) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="text-end">
            <a href="<?= URL_BASE ?>/produits/liste.php" class="btn btn-outline-secondary">
                Continuer mes achats
            </a>
            <a href="<?= URL_BASE ?>/commandes/passer_commande.php" class="btn btn-primary btn-lg">
                Passer commande
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
