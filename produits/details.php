<?php
// ============================================================
//  produits/details.php — Fiche produit complète
// ============================================================
require_once __DIR__ . '/../includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    flashMessage('danger', 'Produit introuvable.');
    rediriger(URL_BASE . '/produits/liste.php');
}

$pdo = getPDO();

// Produit + promo active
$stmt = $pdo->prepare("
    SELECT p.*, c.nom AS nomCategorie,
           pr.typeReduction, pr.valeur AS valeurPromo,
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
    WHERE p.idProduit = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();

if (!$p) {
    flashMessage('danger', 'Produit introuvable.');
    rediriger(URL_BASE . '/produits/liste.php');
}

// Note moyenne
$stmtNote = $pdo->prepare(
    'SELECT ROUND(AVG(note), 1) AS moyenne, COUNT(*) AS nb FROM avis WHERE idProduit = :id'
);
$stmtNote->execute([':id' => $id]);
$noteData = $stmtNote->fetch();

// Avis (5 derniers)
$stmtAvis = $pdo->prepare("
    SELECT a.note, a.commentaire, a.dateAvis, u.prenom, u.nom
    FROM avis a
    JOIN utilisateurs u ON u.id = a.idClient
    WHERE a.idProduit = :id
    ORDER BY a.dateAvis DESC
    LIMIT 5
");
$stmtAvis->execute([':id' => $id]);
$avis = $stmtAvis->fetchAll();

$titrePage = $p['nom'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- Fil d'Ariane -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= URL_BASE ?>/produits/liste.php">Produits</a></li>
            <li class="breadcrumb-item">
                <a href="<?= URL_BASE ?>/produits/liste.php?idCategorie=<?= (int)$p['idCategorie'] ?>">
                    <?= h($p['nomCategorie']) ?>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page"><?= h($p['nom']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Image -->
        <div class="col-md-5">
            <img src="<?= URL_PRODUITS ?>/<?= h($p['image'] ?? 'placeholder.jpg') ?>"
                 class="img-fluid rounded shadow-sm w-100 object-fit-cover"
                 style="max-height:400px;"
                 alt="<?= h($p['nom']) ?>"
                 onerror="this.src='<?= URL_IMAGES ?>/placeholder.jpg'">
        </div>

        <!-- Infos produit -->
        <div class="col-md-7">
            <span class="text-muted small text-uppercase"><?= h($p['nomCategorie']) ?></span>
            <h1 class="h2 mt-1 mb-3"><?= h($p['nom']) ?></h1>

            <!-- Note -->
            <?php if ((int)$noteData['nb'] > 0): ?>
            <div class="d-flex align-items-center gap-2 mb-3">
                <div>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi bi-star<?= $i <= round((float)$noteData['moyenne']) ? '-fill text-warning' : ' text-muted' ?>"></i>
                    <?php endfor; ?>
                </div>
                <span class="small text-muted"><?= h($noteData['moyenne']) ?> / 5
                    (<?= (int)$noteData['nb'] ?> avis)</span>
            </div>
            <?php endif; ?>

            <!-- Prix -->
            <div class="mb-4">
                <?php if ($p['prixPromo'] !== null): ?>
                    <span class="fs-3 fw-bold"><?= formaterPrix($p['prixPromo']) ?></span>
                    <span class="fs-5 text-muted text-decoration-line-through ms-3">
                        <?= formaterPrix($p['prix']) ?>
                    </span>
                    <?php if ($p['typeReduction'] === 'pourcentage'): ?>
                        <span class="etiquette-promo">-<?= (int)$p['valeurPromo'] ?>%</span>
                    <?php else: ?>
                        <span class="etiquette-promo">-<?= formaterPrix($p['valeurPromo']) ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="fs-3 fw-bold"><?= formaterPrix($p['prix']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <p class="text-secondary mb-4"><?= nl2br(h($p['description'])) ?></p>

            <!-- Stock & ajout panier -->
            <?php if ((int)$p['stock'] > 0): ?>
                <p class="text-success small mb-3">
                    <i class="bi bi-check-circle me-1"></i>
                    En stock
                    <?= (int)$p['stock'] <= 5 ? '(plus que ' . (int)$p['stock'] . ')' : '' ?>
                </p>
                <?php if (estConnecte() && !estAdmin()): ?>
                <form method="POST" action="<?= URL_BASE ?>/panier/ajouter.php">
                    <input type="hidden" name="csrf_token" value="<?= genererToken() ?>">
                    <input type="hidden" name="idProduit"  value="<?= (int)$p['idProduit'] ?>">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <label for="quantite" class="form-label mb-0">Qté</label>
                        <input type="number" id="quantite" name="quantite"
                               class="form-control" style="width:80px;"
                               value="1" min="1" max="<?= (int)$p['stock'] ?>">
                    </div>
                    <button type="submit" class="btn btn-dark btn-lg">
                        <i class="bi bi-bag-plus me-2"></i>Ajouter au panier
                    </button>
                </form>
                <?php else: ?>
                <a href="<?= URL_BASE ?>/auth/connexion.php" class="btn btn-dark btn-lg">
                    <i class="bi bi-person me-2"></i>Connectez-vous pour acheter
                </a>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-danger fw-semibold">
                    <i class="bi bi-x-circle me-1"></i>Produit indisponible
                </p>
            <?php endif; ?>

            <!-- Bouton favori -->
            <?php if (estConnecte() && !estAdmin()): ?>
            <div class="mt-3">
                <form method="POST" action="<?= URL_BASE ?>/favoris/ajouter.php">
                    <input type="hidden" name="csrf_token" value="<?= genererToken() ?>">
                    <input type="hidden" name="idProduit" value="<?= (int)$p['idProduit'] ?>">
                    <input type="hidden" name="redirect" value="<?= h(URL_BASE . '/produits/details.php?id=' . $p['idProduit']) ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-heart me-1"></i>Ajouter aux favoris
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section avis -->
    <section class="mt-5 pt-4 border-top">
        <h2 class="h4 mb-4">Avis clients (<?= (int)$noteData['nb'] ?>)</h2>

        <?php if (empty($avis)): ?>
            <p class="text-muted">Aucun avis pour le moment. Soyez le premier !</p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($avis as $a): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <strong><?= h($a['prenom']) ?> <?= h(mb_substr($a['nom'], 0, 1)) ?>.</strong>
                                <span class="small text-muted">
                                    <?= h(date('d/m/Y', strtotime($a['dateAvis']))) ?>
                                </span>
                            </div>
                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= (int)$a['note'] ? '-fill text-warning' : ' text-muted' ?> small"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if ($a['commentaire']): ?>
                                <p class="mb-0 small"><?= nl2br(h($a['commentaire'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (estConnecte() && !estAdmin()): ?>
        <div class="mt-4">
            <a href="<?= URL_BASE ?>/avis/ajouter_avis.php?idProduit=<?= (int)$p['idProduit'] ?>"
               class="btn btn-outline-dark">
                <i class="bi bi-pencil-square me-2"></i>Laisser un avis
            </a>
        </div>
        <?php endif; ?>
    </section>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
