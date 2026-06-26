<?php
// ============================================================
//  produits/liste.php — Catalogue public avec pagination
// ============================================================
require_once __DIR__ . '/../includes/functions.php';

$pdo = getPDO();

// --- Catégories pour le filtre ---
$categories = $pdo->query('SELECT idCategorie, nom FROM categories ORDER BY nom')->fetchAll();

// --- Paramètres GET ---
$idCategorie = filter_input(INPUT_GET, 'idCategorie', FILTER_VALIDATE_INT) ?: null;
$page        = max(1, (int) ($_GET['page'] ?? 1));
$parPage     = PRODUITS_PAR_PAGE;
$offset      = ($page - 1) * $parPage;

// --- Comptage total ---
$whereClause = $idCategorie ? 'WHERE p.idCategorie = :idCat' : '';
$stmtCount   = $pdo->prepare("SELECT COUNT(*) FROM produits p $whereClause");
if ($idCategorie) $stmtCount->bindValue(':idCat', $idCategorie, PDO::PARAM_INT);
$stmtCount->execute();
$total   = (int) $stmtCount->fetchColumn();
$nbPages = (int) ceil($total / $parPage);

// --- Récupération produits avec promo active ---
$sql = "
    SELECT p.*,
           c.nom AS nomCategorie,
           pr.typeReduction,
           pr.valeur AS valeurPromo,
           CASE
               WHEN pr.typeReduction = 'pourcentage'
                    THEN ROUND(p.prix - (p.prix * pr.valeur / 100), 2)
               WHEN pr.typeReduction = 'montant'
                    THEN GREATEST(0, ROUND(p.prix - pr.valeur, 2))
               ELSE NULL
           END AS prixPromo,
           av.noteMoyenne,
           av.nbAvis
    FROM produits p
    JOIN categories c ON c.idCategorie = p.idCategorie
    LEFT JOIN promotions pr ON pr.idProduit = p.idProduit
        AND pr.actif = 1
        AND CURDATE() BETWEEN pr.dateDebut AND pr.dateFin
    LEFT JOIN (
        SELECT idProduit, ROUND(AVG(note), 1) AS noteMoyenne, COUNT(*) AS nbAvis
        FROM avis
        GROUP BY idProduit
    ) av ON av.idProduit = p.idProduit
    $whereClause
    ORDER BY p.nom
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
if ($idCategorie) $stmt->bindValue(':idCat', $idCategorie, PDO::PARAM_INT);
$stmt->bindValue(':limit',  $parPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$produits = $stmt->fetchAll();

// Nom catégorie active
$nomCatActive = '';
if ($idCategorie) {
    foreach ($categories as $cat) {
        if ((int)$cat['idCategorie'] === $idCategorie) {
            $nomCatActive = $cat['nom'];
            break;
        }
    }
}

$titrePage = $nomCatActive ? $nomCatActive : 'Tous les produits';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
        <h1 class="h3 mb-0"><?= $nomCatActive ? h($nomCatActive) : 'Tous les produits' ?></h1>
        <span class="text-muted small"><?= $total ?> produit<?= $total > 1 ? 's' : '' ?></span>
    </div>

    <div class="row g-4">
        <!-- Sidebar filtres -->
        <aside class="col-lg-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Catégories</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-1">
                            <a href="<?= URL_BASE ?>/produits/liste.php"
                               class="text-decoration-none <?= !$idCategorie ? 'fw-bold text-dark' : 'text-secondary' ?>">
                                Toutes les catégories
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                        <li class="mb-1">
                            <a href="<?= URL_BASE ?>/produits/liste.php?idCategorie=<?= (int)$cat['idCategorie'] ?>"
                               class="text-decoration-none <?= (int)$cat['idCategorie'] === $idCategorie ? 'fw-bold text-dark' : 'text-secondary' ?>">
                                <?= h($cat['nom']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- Grille produits -->
        <div class="col-lg-9">
            <?php if (empty($produits)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-box-seam fs-1 d-block mb-3"></i>
                    Aucun produit dans cette catégorie.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($produits as $p): ?>
                    <div class="col-sm-6 col-md-4">
                        <div class="card h-100 shadow-sm">
                            <a href="<?= URL_BASE ?>/produits/details.php?id=<?= (int)$p['idProduit'] ?>">
                                <img src="<?= URL_PRODUITS ?>/<?= h($p['image'] ?? 'placeholder.jpg') ?>"
                                     class="card-img-top object-fit-cover" style="height:200px;"
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

                                <!-- Étiquette ingrédient : note moyenne + pastille de stock -->
                                <div class="etiquette-ingredient">
                                    <span class="etiquette-note">
                                        <?php if ($p['nbAvis']): ?>
                                            <i class="bi bi-star-fill"></i> <?= h($p['noteMoyenne']) ?>
                                            <span class="etiquette-nbavis">(<?= (int)$p['nbAvis'] ?>)</span>
                                        <?php else: ?>
                                            <span class="etiquette-nbavis">Pas encore d'avis</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="etiquette-stock etiquette-stock--<?= (int)$p['stock'] === 0 ? 'rupture' : ((int)$p['stock'] <= 5 ? 'faible' : 'dispo') ?>"
                                          title="<?= (int)$p['stock'] === 0 ? 'Rupture de stock' : ((int)$p['stock'] <= 5 ? 'Stock faible' : 'En stock') ?>">
                                        <span class="etiquette-pastille"></span>
                                    </span>
                                </div>

                                <div class="mt-auto pt-2">
                                    <?php if ($p['prixPromo'] !== null): ?>
                                        <span class="fw-bold"><?= formaterPrix($p['prixPromo']) ?></span>
                                        <span class="text-muted text-decoration-line-through ms-2 small"><?= formaterPrix($p['prix']) ?></span>
                                    <?php else: ?>
                                        <span class="fw-bold"><?= formaterPrix($p['prix']) ?></span>
                                    <?php endif; ?>
                                    <?php if ((int)$p['stock'] === 0): ?>
                                        <span class="text-muted small ms-2">Rupture</span>
                                    <?php elseif ((int)$p['stock'] <= 5): ?>
                                        <span class="text-muted small ms-2">Plus que <?= (int)$p['stock'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($nbPages > 1): ?>
                <nav class="mt-5" aria-label="Pagination">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $nbPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
