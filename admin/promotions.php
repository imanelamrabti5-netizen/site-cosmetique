<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonAdmin();

$pdo = getPDO();
$typesValides = ['pourcentage', 'montant'];

// --- Suppression ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer') {
    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger', 'Requête invalide.');
        rediriger(URL_BASE . '/admin/promotions.php');
    }

    $idPromotion = (int) ($_POST['idPromotion'] ?? 0);
    $pdo->prepare("DELETE FROM promotions WHERE idPromotion = ?")->execute([$idPromotion]);
    flashMessage('success', 'Promotion supprimée.');
    rediriger(URL_BASE . '/admin/promotions.php');
}

// --- Création / modification ---
$erreurs = [];
$promotionEnEdition = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enregistrer') {
    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Requête invalide, merci de réessayer.';
    }

    $idPromotion = (int) ($_POST['idPromotion'] ?? 0);
    $idProduit = (int) ($_POST['idProduit'] ?? 0);
    $typeReduction = $_POST['typeReduction'] ?? '';
    $valeur = (float) ($_POST['valeur'] ?? 0);
    $dateDebut = $_POST['dateDebut'] ?? '';
    $dateFin = $_POST['dateFin'] ?? '';
    $actif = isset($_POST['actif']) ? 1 : 0;

    if ($idProduit <= 0) {
        $erreurs[] = 'Merci de choisir un produit.';
    }
    if (!in_array($typeReduction, $typesValides, true)) {
        $erreurs[] = 'Type de réduction invalide.';
    }
    if ($valeur <= 0) {
        $erreurs[] = 'La valeur de la réduction doit être supérieure à 0.';
    }
    if ($typeReduction === 'pourcentage' && $valeur > 100) {
        $erreurs[] = 'Un pourcentage de réduction ne peut pas dépasser 100.';
    }
    if ($dateDebut === '' || $dateFin === '' || strtotime($dateFin) < strtotime($dateDebut)) {
        $erreurs[] = 'Les dates de début et de fin doivent être valides (fin >= début).';
    }

    if (empty($erreurs)) {
        if ($idPromotion > 0) {
            $stmt = $pdo->prepare("UPDATE promotions
                                    SET idProduit = ?, typeReduction = ?, valeur = ?, dateDebut = ?, dateFin = ?, actif = ?
                                    WHERE idPromotion = ?");
            $stmt->execute([$idProduit, $typeReduction, $valeur, $dateDebut, $dateFin, $actif, $idPromotion]);
            flashMessage('success', 'Promotion mise à jour.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO promotions (idProduit, typeReduction, valeur, dateDebut, dateFin, actif)
                                    VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$idProduit, $typeReduction, $valeur, $dateDebut, $dateFin, $actif]);
            flashMessage('success', 'Promotion créée.');
        }
        rediriger(URL_BASE . '/admin/promotions.php');
    } else {
        // Réafficher le formulaire avec les valeurs saisies en cas d'erreur
        $promotionEnEdition = [
            'idPromotion' => $idPromotion,
            'idProduit' => $idProduit,
            'typeReduction' => $typeReduction,
            'valeur' => $valeur,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'actif' => $actif,
        ];
    }
}

// --- Affichage du formulaire d'édition (GET) ---
if ($promotionEnEdition === null && ($_GET['action'] ?? '') === 'modifier') {
    $idPromotion = (int) ($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE idPromotion = ?");
    $stmt->execute([$idPromotion]);
    $promotionEnEdition = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promotionEnEdition) {
        flashMessage('danger', 'Promotion introuvable.');
        rediriger(URL_BASE . '/admin/promotions.php');
    }
}

$afficherFormulaire = $promotionEnEdition !== null || ($_GET['action'] ?? '') === 'ajouter';

// --- Liste des produits pour le menu déroulant ---
$produits = $pdo->query("SELECT idProduit, nom FROM produits ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- Liste des promotions ---
$sql = "SELECT pr.idPromotion, pr.typeReduction, pr.valeur, pr.dateDebut, pr.dateFin, pr.actif,
               p.nom AS nomProduit
        FROM promotions pr
        JOIN produits p ON pr.idProduit = p.idProduit
        ORDER BY pr.dateDebut DESC";
$promotions = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Gestion des promotions</h1>
        <?php if (!$afficherFormulaire): ?>
            <a href="<?= URL_BASE ?>/admin/promotions.php?action=ajouter" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nouvelle promotion
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($erreurs)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= h($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($afficherFormulaire): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= ($promotionEnEdition['idPromotion'] ?? 0) > 0 ? 'Modifier la promotion' : 'Nouvelle promotion' ?></h2>
                <form action="<?= URL_BASE ?>/admin/promotions.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= h(genererToken()) ?>">
                    <input type="hidden" name="action" value="enregistrer">
                    <input type="hidden" name="idPromotion" value="<?= (int) ($promotionEnEdition['idPromotion'] ?? 0) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Produit</label>
                            <select name="idProduit" class="form-select" required>
                                <option value="">Choisir un produit</option>
                                <?php foreach ($produits as $produit): ?>
                                    <option value="<?= (int) $produit['idProduit'] ?>"
                                        <?= (int) ($promotionEnEdition['idProduit'] ?? 0) === (int) $produit['idProduit'] ? 'selected' : '' ?>>
                                        <?= h($produit['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type de réduction</label>
                            <select name="typeReduction" class="form-select" required>
                                <option value="pourcentage" <?= ($promotionEnEdition['typeReduction'] ?? '') === 'pourcentage' ? 'selected' : '' ?>>Pourcentage (%)</option>
                                <option value="montant" <?= ($promotionEnEdition['typeReduction'] ?? '') === 'montant' ? 'selected' : '' ?>>Montant fixe</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valeur</label>
                            <input type="number" step="0.01" min="0" name="valeur" class="form-control"
                                   value="<?= h($promotionEnEdition['valeur'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date de début</label>
                            <input type="date" name="dateDebut" class="form-control"
                                   value="<?= h($promotionEnEdition['dateDebut'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date de fin</label>
                            <input type="date" name="dateFin" class="form-control"
                                   value="<?= h($promotionEnEdition['dateFin'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" name="actif" class="form-check-input" id="actif"
                                    <?= ($promotionEnEdition['actif'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="actif">Promotion active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="<?= URL_BASE ?>/admin/promotions.php" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Type</th>
                    <th>Valeur</th>
                    <th>Période</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promotions as $promo): ?>
                    <tr>
                        <td><?= h($promo['nomProduit']) ?></td>
                        <td><?= $promo['typeReduction'] === 'pourcentage' ? 'Pourcentage' : 'Montant fixe' ?></td>
                        <td><?= $promo['typeReduction'] === 'pourcentage' ? h($promo['valeur']) . ' %' : formaterPrix($promo['valeur']) ?></td>
                        <td><?= h(date('d/m/Y', strtotime($promo['dateDebut']))) ?> → <?= h(date('d/m/Y', strtotime($promo['dateFin']))) ?></td>
                        <td>
                            <?= $promo['actif']
                                ? '<span class="badge bg-success">Active</span>'
                                : '<span class="badge bg-secondary">Inactive</span>' ?>
                        </td>
                        <td class="d-flex gap-1">
                            <a href="<?= URL_BASE ?>/admin/promotions.php?action=modifier&id=<?= (int) $promo['idPromotion'] ?>"
                               class="btn btn-sm btn-outline-secondary">Modifier</a>
                            <form action="<?= URL_BASE ?>/admin/promotions.php" method="post"
                                  onsubmit="return confirm('Supprimer cette promotion ?');">
                                <input type="hidden" name="csrf_token" value="<?= h(genererToken()) ?>">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="idPromotion" value="<?= (int) $promo['idPromotion'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($promotions)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Aucune promotion enregistrée.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
