<?php
// ============================================================
//  admin/categories.php — CRUD catégories (back-office)
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
redirigerSiNonAdmin();

$pdo    = getPDO();
$action = $_GET['action'] ?? 'liste';
$erreurs = [];

// ============================================================
//  SUPPRESSION
// ============================================================
if ($action === 'supprimer' && isset($_GET['id'])) {
    if (!verifierToken($_GET['csrf_token'] ?? '')) {
        flashMessage('danger', 'Action non autorisée.');
    } else {
        $idDel = (int) $_GET['id'];
        // Vérifier qu'aucun produit n'est rattaché
        $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM produits WHERE idCategorie = :id');
        $stmtCheck->execute([':id' => $idDel]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            flashMessage('danger', 'Impossible de supprimer : des produits utilisent cette catégorie.');
        } else {
            $pdo->prepare('DELETE FROM categories WHERE idCategorie = :id')->execute([':id' => $idDel]);
            flashMessage('success', 'Catégorie supprimée.');
        }
    }
    rediriger(URL_BASE . '/admin/categories.php');
}

// ============================================================
//  CRÉATION / MODIFICATION — traitement POST
// ============================================================
if (in_array($action, ['creer', 'modifier']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Token de sécurité invalide.';
    } else {
        $idCat = (int) ($_POST['idCategorie'] ?? 0);
        $nom   = trim($_POST['nom']           ?? '');
        $desc  = trim($_POST['description']   ?? '');

        if (empty($nom)) $erreurs[] = 'Le nom de la catégorie est obligatoire.';

        if (empty($erreurs)) {
            if ($action === 'creer') {
                $stmt = $pdo->prepare('INSERT INTO categories (nom, description) VALUES (:nom, :desc)');
                $stmt->execute([':nom' => $nom, ':desc' => $desc ?: null]);
                flashMessage('success', 'Catégorie créée.');
            } else {
                $stmt = $pdo->prepare('UPDATE categories SET nom=:nom, description=:desc WHERE idCategorie=:id');
                $stmt->execute([':nom' => $nom, ':desc' => $desc ?: null, ':id' => $idCat]);
                flashMessage('success', 'Catégorie modifiée.');
            }
            rediriger(URL_BASE . '/admin/categories.php');
        }
    }
}

// --- Catégorie à modifier ---
$categorie = null;
if ($action === 'modifier' && isset($_GET['id'])) {
    $stmtC = $pdo->prepare('SELECT * FROM categories WHERE idCategorie = :id LIMIT 1');
    $stmtC->execute([':id' => (int)$_GET['id']]);
    $categorie = $stmtC->fetch();
    if (!$categorie) { flashMessage('danger', 'Catégorie introuvable.'); rediriger(URL_BASE . '/admin/categories.php'); }
}

// --- Liste avec comptage produits ---
$listeCats = $pdo->query(
    'SELECT c.*, COUNT(p.idProduit) AS nbProduits
     FROM categories c
     LEFT JOIN produits p ON p.idCategorie = c.idCategorie
     GROUP BY c.idCategorie
     ORDER BY c.nom'
)->fetchAll();

$titrePage = 'Admin — Catégories';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:860px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Gestion des catégories</h1>
        <a href="?action=creer" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Nouvelle catégorie
        </a>
    </div>

    <?php if (in_array($action, ['creer', 'modifier'])): ?>
    <!-- FORMULAIRE -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <?= $action === 'creer' ? 'Créer une catégorie' : 'Modifier : ' . h($categorie['nom'] ?? '') ?>
        </div>
        <div class="card-body" style="max-width:500px;">
            <?php if ($erreurs): ?>
                <div class="alert alert-danger"><ul class="mb-0">
                    <?php foreach ($erreurs as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                </ul></div>
            <?php endif; ?>
            <form method="POST" action="?action=<?= h($action) ?><?= $categorie ? '&id=' . (int)$categorie['idCategorie'] : '' ?>">
                <input type="hidden" name="csrf_token"   value="<?= genererToken() ?>">
                <input type="hidden" name="idCategorie"  value="<?= (int)($categorie['idCategorie'] ?? 0) ?>">

                <div class="mb-3">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom" class="form-control" required
                           value="<?= h($categorie['nom'] ?? ($_POST['nom'] ?? '')) ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"><?= h($categorie['description'] ?? ($_POST['description'] ?? '')) ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">
                        <?= $action === 'creer' ? 'Créer' : 'Enregistrer' ?>
                    </button>
                    <a href="<?= URL_BASE ?>/admin/categories.php" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- LISTE -->
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                        <th class="text-center">Produits</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listeCats)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Aucune catégorie.</td></tr>
                    <?php else: ?>
                    <?php foreach ($listeCats as $cat): ?>
                    <tr>
                        <td class="fw-semibold"><?= h($cat['nom']) ?></td>
                        <td class="text-muted small"><?= h(mb_strimwidth($cat['description'] ?? '', 0, 80, '…')) ?></td>
                        <td class="text-center">
                            <a href="<?= URL_BASE ?>/admin/produits.php?idCategorie=<?= (int)$cat['idCategorie'] ?>"
                               class="badge bg-light text-dark border text-decoration-none">
                                <?= (int)$cat['nbProduits'] ?>
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="?action=modifier&id=<?= (int)$cat['idCategorie'] ?>"
                               class="btn btn-outline-secondary btn-sm me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="?action=supprimer&id=<?= (int)$cat['idCategorie'] ?>&csrf_token=<?= genererToken() ?>"
                               class="btn btn-outline-danger btn-sm"
                               onclick="return confirm('Supprimer cette catégorie ?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
