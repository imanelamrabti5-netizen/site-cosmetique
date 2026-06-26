<?php
// ============================================================
//  admin/produits.php — CRUD produits (back-office)
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
redirigerSiNonAdmin();

$pdo    = getPDO();
$action = $_GET['action'] ?? 'liste';
$erreurs = [];

// --- Catégories (utiles pour les formulaires) ---
$categories = $pdo->query('SELECT idCategorie, nom FROM categories ORDER BY nom')->fetchAll();

// ============================================================
//  SUPPRESSION
// ============================================================
if ($action === 'supprimer' && isset($_GET['id'])) {
    if (!verifierToken($_GET['csrf_token'] ?? '')) {
        flashMessage('danger', 'Action non autorisée.');
    } else {
        $idDel = (int) $_GET['id'];
        // Récupérer l'image pour la supprimer du disque
        $stmtImg = $pdo->prepare('SELECT image FROM produits WHERE idProduit = :id');
        $stmtImg->execute([':id' => $idDel]);
        $img = $stmtImg->fetchColumn();
        if ($img && file_exists(UPLOAD_DIR . $img)) {
            unlink(UPLOAD_DIR . $img);
        }
        $pdo->prepare('DELETE FROM produits WHERE idProduit = :id')->execute([':id' => $idDel]);
        flashMessage('success', 'Produit supprimé.');
    }
    rediriger(URL_BASE . '/admin/produits.php');
}

// ============================================================
//  CRÉATION / MODIFICATION — traitement POST
// ============================================================
if (in_array($action, ['creer', 'modifier']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Token de sécurité invalide.';
    } else {
        $idProduit  = (int) ($_POST['idProduit'] ?? 0);
        $nom        = trim($_POST['nom']         ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $prix       = (float) str_replace(',', '.', $_POST['prix'] ?? '');
        $stock      = (int) ($_POST['stock']     ?? 0);
        $idCat      = (int) ($_POST['idCategorie'] ?? 0);

        if (empty($nom))   $erreurs[] = 'Le nom est obligatoire.';
        if ($prix <= 0)    $erreurs[] = 'Le prix doit être supérieur à 0.';
        if ($idCat === 0)  $erreurs[] = 'Veuillez choisir une catégorie.';

        // Upload image
        $nomImage = $_POST['imageActuelle'] ?? '';
        if (!empty($_FILES['image']['name'])) {
            $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $extsOk   = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $extsOk)) {
                $erreurs[] = 'Format d\'image non accepté (jpg, png, webp).';
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $erreurs[] = 'L\'image ne doit pas dépasser 2 Mo.';
            } else {
                $nomImage = uniqid('prod_', true) . '.' . $ext;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $nomImage)) {
                    $erreurs[] = 'Échec de l\'upload de l\'image.';
                    $nomImage = $_POST['imageActuelle'] ?? '';
                }
            }
        }

        if (empty($erreurs)) {
            if ($action === 'creer') {
                $stmt = $pdo->prepare(
                    'INSERT INTO produits (nom, description, prix, stock, image, idCategorie)
                     VALUES (:nom, :desc, :prix, :stock, :image, :idCat)'
                );
                $stmt->execute([':nom'=>$nom,':desc'=>$desc,':prix'=>$prix,
                                ':stock'=>$stock,':image'=>$nomImage,':idCat'=>$idCat]);
                flashMessage('success', 'Produit créé avec succès.');
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE produits SET nom=:nom, description=:desc, prix=:prix,
                     stock=:stock, image=:image, idCategorie=:idCat
                     WHERE idProduit=:id'
                );
                $stmt->execute([':nom'=>$nom,':desc'=>$desc,':prix'=>$prix,
                                ':stock'=>$stock,':image'=>$nomImage,':idCat'=>$idCat,
                                ':id'=>$idProduit]);
                flashMessage('success', 'Produit modifié avec succès.');
            }
            rediriger(URL_BASE . '/admin/produits.php');
        }
    }
}

// --- Produit à modifier ---
$produit = null;
if ($action === 'modifier' && isset($_GET['id'])) {
    $stmtP = $pdo->prepare('SELECT * FROM produits WHERE idProduit = :id LIMIT 1');
    $stmtP->execute([':id' => (int)$_GET['id']]);
    $produit = $stmtP->fetch();
    if (!$produit) { flashMessage('danger', 'Produit introuvable.'); rediriger(URL_BASE . '/admin/produits.php'); }
}

// --- Liste avec recherche ---
$recherche = trim($_GET['q'] ?? '');
if ($recherche !== '') {
    $motif      = '%' . $recherche . '%';
    $stmtListe  = $pdo->prepare(
        'SELECT p.*, c.nom AS nomCategorie FROM produits p
         JOIN categories c ON c.idCategorie = p.idCategorie
         WHERE p.nom LIKE :m OR p.description LIKE :m2
         ORDER BY p.nom LIMIT 100'
    );
    $stmtListe->execute([':m' => $motif, ':m2' => $motif]);
} else {
    $idCatFiltre = filter_input(INPUT_GET, 'idCategorie', FILTER_VALIDATE_INT) ?: null;
    if ($idCatFiltre) {
        $stmtListe = $pdo->prepare(
            'SELECT p.*, c.nom AS nomCategorie FROM produits p
             JOIN categories c ON c.idCategorie = p.idCategorie
             WHERE p.idCategorie = :idCat ORDER BY p.nom LIMIT 100'
        );
        $stmtListe->execute([':idCat' => $idCatFiltre]);
    } else {
        $stmtListe = $pdo->query(
            'SELECT p.*, c.nom AS nomCategorie FROM produits p
             JOIN categories c ON c.idCategorie = p.idCategorie
             ORDER BY p.nom LIMIT 100'
        );
    }
}
$listeProduits = $stmtListe->fetchAll();

$titrePage = 'Admin — Produits';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Gestion des produits</h1>
        <a href="?action=creer" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Nouveau produit
        </a>
    </div>

    <?php if (in_array($action, ['creer', 'modifier'])): ?>
    <!-- ===== FORMULAIRE CRÉATION / MODIFICATION ===== -->
    <div class="card shadow-sm mb-4" style="max-width:700px;">
        <div class="card-header">
            <?= $action === 'creer' ? 'Créer un produit' : 'Modifier : ' . h($produit['nom'] ?? '') ?>
        </div>
        <div class="card-body">
            <?php if ($erreurs): ?>
                <div class="alert alert-danger"><ul class="mb-0">
                    <?php foreach ($erreurs as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                </ul></div>
            <?php endif; ?>
            <form method="POST" action="?action=<?= h($action) ?><?= $produit ? '&id=' . (int)$produit['idProduit'] : '' ?>"
                  enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token"    value="<?= genererToken() ?>">
                <input type="hidden" name="idProduit"     value="<?= (int)($produit['idProduit'] ?? 0) ?>">
                <input type="hidden" name="imageActuelle" value="<?= h($produit['image'] ?? '') ?>">

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" class="form-control" required
                               value="<?= h($produit['nom'] ?? ($_POST['nom'] ?? '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= h($produit['description'] ?? ($_POST['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Prix (MAD) <span class="text-danger">*</span></label>
                        <input type="number" name="prix" class="form-control" step="0.01" min="0" required
                               value="<?= h($produit['prix'] ?? ($_POST['prix'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stock</label>
                        <input type="number" name="stock" class="form-control" min="0"
                               value="<?= (int)($produit['stock'] ?? ($_POST['stock'] ?? 0)) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                        <select name="idCategorie" class="form-select" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['idCategorie'] ?>"
                                <?= (int)($produit['idCategorie'] ?? ($_POST['idCategorie'] ?? 0)) === (int)$cat['idCategorie'] ? 'selected' : '' ?>>
                                <?= h($cat['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Image (jpg/png/webp, max 2 Mo)</label>
                        <?php if (!empty($produit['image'])): ?>
                            <div class="mb-2">
                                <img src="<?= URL_PRODUITS ?>/<?= h($produit['image']) ?>"
                                     style="height:80px;" class="rounded"
                                     onerror="this.style.display='none'">
                                <span class="small text-muted ms-2"><?= h($produit['image']) ?></span>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-dark">
                        <?= $action === 'creer' ? 'Créer' : 'Enregistrer' ?>
                    </button>
                    <a href="<?= URL_BASE ?>/admin/produits.php" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== LISTE ===== -->
    <!-- Filtres -->
    <div class="d-flex flex-wrap gap-2 mb-3">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Rechercher…" value="<?= h($recherche) ?>">
            <button class="btn btn-outline-secondary btn-sm">Filtrer</button>
        </form>
        <form method="GET" class="d-flex gap-2">
            <select name="idCategorie" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Toutes catégories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['idCategorie'] ?>"><?= h($cat['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">Image</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listeProduits)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun produit trouvé.</td></tr>
                    <?php else: ?>
                    <?php foreach ($listeProduits as $p): ?>
                    <tr>
                        <td>
                            <img src="<?= URL_PRODUITS ?>/<?= h($p['image'] ?? '') ?>"
                                 style="width:48px;height:48px;object-fit:cover;" class="rounded"
                                 onerror="this.style.display='none'">
                        </td>
                        <td><?= h($p['nom']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= h($p['nomCategorie']) ?></span></td>
                        <td><?= formaterPrix($p['prix']) ?></td>
                        <td>
                            <?php if ((int)$p['stock'] === 0): ?>
                                <span class="badge bg-danger">Rupture</span>
                            <?php elseif ((int)$p['stock'] <= 5): ?>
                                <span class="badge bg-warning text-dark"><?= (int)$p['stock'] ?></span>
                            <?php else: ?>
                                <span class="text-success fw-semibold"><?= (int)$p['stock'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="?action=modifier&id=<?= (int)$p['idProduit'] ?>"
                               class="btn btn-outline-secondary btn-sm me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="?action=supprimer&id=<?= (int)$p['idProduit'] ?>&csrf_token=<?= genererToken() ?>"
                               class="btn btn-outline-danger btn-sm"
                               onclick="return confirm('Supprimer ce produit ?')">
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
