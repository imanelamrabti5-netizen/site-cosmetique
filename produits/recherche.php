<?php
// ============================================================
//  produits/recherche.php — Recherche par mot-clé
// ============================================================
require_once __DIR__ . '/../includes/functions.php';

$pdo = getPDO();
$q   = trim($_GET['q'] ?? '');
$produits = [];
$nbResultats = 0;

if ($q !== '') {
    $motif = '%' . $q . '%';

    $stmtCount = $pdo->prepare(
        'SELECT COUNT(*) FROM produits p
         WHERE p.nom LIKE :m1 OR p.description LIKE :m2'
    );
    $stmtCount->execute([':m1' => $motif, ':m2' => $motif]);
    $nbResultats = (int) $stmtCount->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT p.*, c.nom AS nomCategorie,
               CASE
                   WHEN pr.typeReduction = 'pourcentage'
                        THEN ROUND(p.prix - (p.prix * pr.valeur / 100), 2)
                   WHEN pr.typeReduction = 'montant'
                        THEN GREATEST(0, ROUND(p.prix - pr.valeur, 2))
                   ELSE NULL
               END AS prixPromo
        FROM produits p
        JOIN categories c ON c.idCategorie = p.idCategorie
        LEFT JOIN promotions pr ON pr.idProduit = p.idProduit
            AND pr.actif = 1 AND CURDATE() BETWEEN pr.dateDebut AND pr.dateFin
        WHERE p.nom LIKE :m1 OR p.description LIKE :m2
        ORDER BY p.nom
        LIMIT 50
    ");
    $stmt->execute([':m1' => $motif, ':m2' => $motif]);
    $produits = $stmt->fetchAll();
}

$titrePage = 'Recherche';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:900px;">
    <h1 class="h3 mb-4">Recherche</h1>

    <!-- Formulaire -->
    <form method="GET" action="" class="mb-4">
        <div class="input-group input-group-lg">
            <input type="search" name="q" class="form-control"
                   placeholder="Nom, ingrédient, description…"
                   value="<?= h($q) ?>" autofocus>
            <button class="btn btn-dark" type="submit">
                <i class="bi bi-search me-1"></i>Rechercher
            </button>
        </div>
    </form>

    <?php if ($q !== ''): ?>
        <p class="text-muted mb-4">
            <?= $nbResultats ?> résultat<?= $nbResultats > 1 ? 's' : '' ?>
            pour <strong><?= h($q) ?></strong>
        </p>

        <?php if (empty($produits)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-search fs-1 d-block mb-3"></i>
                Aucun produit ne correspond à votre recherche.
                <div class="mt-3">
                    <a href="<?= URL_BASE ?>/produits/liste.php" class="btn btn-outline-dark btn-sm">
                        Voir tous les produits
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($produits as $p): ?>
                <div class="col-sm-6 col-md-4">
                    <div class="card h-100 shadow-sm">
                        <a href="<?= URL_BASE ?>/produits/details.php?id=<?= (int)$p['idProduit'] ?>">
                            <img src="<?= URL_PRODUITS ?>/<?= h($p['image'] ?? 'placeholder.jpg') ?>"
                                 class="card-img-top object-fit-cover" style="height:180px;"
                                 alt="<?= h($p['nom']) ?>"
                                 onerror="this.src='<?= URL_IMAGES ?>/placeholder.jpg'">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <span class="text-muted small text-uppercase mb-1"><?= h($p['nomCategorie']) ?></span>
                            <h5 class="card-title fs-6 mb-2">
                                <a href="<?= URL_BASE ?>/produits/details.php?id=<?= (int)$p['idProduit'] ?>"
                                   class="text-dark text-decoration-none stretched-link">
                                    <?= h($p['nom']) ?>
                                </a>
                            </h5>
                            <div class="mt-auto pt-2">
                                <?php if ($p['prixPromo'] !== null): ?>
                                    <span class="fw-bold"><?= formaterPrix($p['prixPromo']) ?></span>
                                    <span class="text-muted text-decoration-line-through ms-2 small">
                                        <?= formaterPrix($p['prix']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="fw-bold"><?= formaterPrix($p['prix']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
