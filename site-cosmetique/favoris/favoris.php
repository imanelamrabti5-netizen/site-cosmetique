<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonConnecte();

$pdo = getPDO();
$idClient = $_SESSION['user_id'];

$sql = "SELECT f.idFavori, p.idProduit, p.nom, p.image, p.prix, p.stock
        FROM favoris f
        JOIN produits p ON f.idProduit = p.idProduit
        WHERE f.idClient = ?
        ORDER BY f.dateAjout DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$idClient]);
$favoris = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Mes favoris</h1>

    <?php if (empty($favoris)): ?>
        <div class="alert alert-info">
            Vous n'avez aucun produit favori pour le moment.
            <a href="<?= URL_BASE ?>/produits/liste.php">Voir les produits</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($favoris as $fav): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card h-100">
                        <img src="<?= URL_PRODUITS . h($fav['image']) ?>" class="card-img-top"
                             alt="<?= h($fav['nom']) ?>" style="height:200px;object-fit:cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= h($fav['nom']) ?></h5>
                            <p class="card-text"><?= formaterPrix($fav['prix']) ?></p>
                            <?php if ($fav['stock'] <= 0): ?>
                                <span class="badge bg-secondary mb-2 align-self-start">Rupture de stock</span>
                            <?php endif; ?>
                            <div class="mt-auto d-flex gap-2">
                                <a href="<?= URL_BASE ?>/produits/details.php?id=<?= (int) $fav['idProduit'] ?>"
                                   class="btn btn-outline-secondary btn-sm flex-fill">Voir</a>
                                <form action="<?= URL_BASE ?>/favoris/supprimer.php" method="post" class="flex-fill">
                                    <input type="hidden" name="csrf_token" value="<?= h(genererToken()) ?>">
                                    <input type="hidden" name="idProduit" value="<?= (int) $fav['idProduit'] ?>">
                                    <input type="hidden" name="redirect" value="<?= h(URL_BASE . '/favoris/favoris.php') ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                        <i class="bi bi-heart-fill"></i> Retirer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
