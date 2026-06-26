<?php
// ============================================================
//  index.php — Page d'accueil
// ============================================================
require_once __DIR__ . '/includes/functions.php';

$pdo = getPDO();

// Produits mis en avant : priorité aux produits en promotion active,
// complétés par les produits les plus récents jusqu'à 4 au total.
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
    ORDER BY (pr.idPromotion IS NOT NULL) DESC, p.idProduit DESC
    LIMIT 4
";
$produitsAvant = $pdo->query($sql)->fetchAll();

$titrePage = 'Accueil';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">

    <!-- Section héro -->
    <section class="hero-accueil">
        <span class="eyebrow d-block mb-2" style="letter-spacing:.12em;text-transform:uppercase;font-size:.78rem;color:var(--vert-sauge-fonce);">
            <?= h(SITE_SLOGAN) ?>
        </span>
        <h1><?= h(SITE_NOM) ?></h1>
        <p class="lead">
            Des soins du visage, du corps et du parfum pensés pour révéler
            une beauté naturelle, avec des ingrédients choisis et une
            transparence totale sur chaque formule.
        </p>
        <a href="<?= URL_BASE ?>/produits/liste.php" class="btn btn-dark btn-lg">
            Découvrir la collection
        </a>
    </section>

    <!-- Produits mis en avant -->
    <section class="mb-5">
        <div class="section-titre">
            <span class="eyebrow">Sélection</span>
            <h2 class="h3 mb-0">Nos coups de cœur du moment</h2>
        </div>

        <?php if (empty($produitsAvant)): ?>
            <p class="text-center text-muted">
                Le catalogue est en cours de préparation, revenez bientôt !
            </p>
        <?php else: ?>
            <div class="row g-4 grille-produits-4">
                <?php foreach ($produitsAvant as $p): ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
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
                                    <?php if ($p['typeReduction'] === 'pourcentage'): ?>
                                        <span class="etiquette-promo">-<?= (int)$p['valeurPromo'] ?>%</span>
                                    <?php else: ?>
                                        <span class="etiquette-promo">-<?= formaterPrix($p['valeurPromo']) ?></span>
                                    <?php endif; ?>
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

        <div class="text-center mt-4">
            <a href="<?= URL_BASE ?>/produits/liste.php" class="btn btn-outline-dark">
                Voir tous les produits
            </a>
        </div>
    </section>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
