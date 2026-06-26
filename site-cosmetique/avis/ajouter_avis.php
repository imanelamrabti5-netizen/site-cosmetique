<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonConnecte();

$pdo = getPDO();
$idClient = $_SESSION['user_id'];
$idProduit = (int) ($_GET['idProduit'] ?? $_POST['idProduit'] ?? 0);

// Vérifier que le produit existe
$stmt = $pdo->prepare("SELECT idProduit, nom FROM produits WHERE idProduit = ?");
$stmt->execute([$idProduit]);
$produit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produit) {
    flashMessage('danger', 'Produit introuvable.');
    rediriger(URL_BASE . '/produits/liste.php');
}

// Vérifier qu'un avis n'existe pas déjà pour ce client sur ce produit
$stmt = $pdo->prepare("SELECT idAvis FROM avis WHERE idClient = ? AND idProduit = ?");
$stmt->execute([$idClient, $idProduit]);
$avisExistant = $stmt->fetch(PDO::FETCH_ASSOC);

if ($avisExistant) {
    flashMessage('info', 'Vous avez déjà laissé un avis sur ce produit.');
    rediriger(URL_BASE . '/produits/details.php?id=' . $idProduit);
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Requête invalide, merci de réessayer.';
    }

    $note = (int) ($_POST['note'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');

    if ($note < 1 || $note > 5) {
        $erreurs[] = 'La note doit être comprise entre 1 et 5.';
    }

    if ($commentaire === '') {
        $erreurs[] = 'Le commentaire ne peut pas être vide.';
    } elseif (mb_strlen($commentaire) > 1000) {
        $erreurs[] = 'Le commentaire est trop long (1000 caractères maximum).';
    }

    if (empty($erreurs)) {
        // Double vérification anti-doublon au moment de l'insertion (concurrence)
        $stmt = $pdo->prepare("SELECT idAvis FROM avis WHERE idClient = ? AND idProduit = ?");
        $stmt->execute([$idClient, $idProduit]);

        if ($stmt->fetch()) {
            flashMessage('info', 'Vous avez déjà laissé un avis sur ce produit.');
            rediriger(URL_BASE . '/produits/details.php?id=' . $idProduit);
        }

        $stmt = $pdo->prepare("INSERT INTO avis (idProduit, idClient, note, commentaire, dateAvis)
                                VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$idProduit, $idClient, $note, $commentaire]);

        flashMessage('success', 'Merci, votre avis a bien été enregistré.');
        rediriger(URL_BASE . '/produits/details.php?id=' . $idProduit);
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h1 class="mb-4">Laisser un avis sur "<?= h($produit['nom']) ?>"</h1>

            <?php if (!empty($erreurs)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= h($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?= URL_BASE ?>/avis/ajouter_avis.php?idProduit=<?= $idProduit ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= h(genererToken()) ?>">
                <input type="hidden" name="idProduit" value="<?= $idProduit ?>">

                <div class="mb-3">
                    <label class="form-label" for="note">Note</label>
                    <select class="form-select" id="note" name="note" required>
                        <option value="">Choisir une note</option>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?= $i ?>" <?= (($_POST['note'] ?? '') == $i) ? 'selected' : '' ?>>
                                <?= str_repeat('★', $i) . str_repeat('☆', 5 - $i) ?> (<?= $i ?>/5)
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="commentaire">Commentaire</label>
                    <textarea class="form-control" id="commentaire" name="commentaire" rows="4" maxlength="1000"
                              required><?= h($_POST['commentaire'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= URL_BASE ?>/produits/details.php?id=<?= $idProduit ?>" class="btn btn-outline-secondary">
                        Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">Publier mon avis</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
