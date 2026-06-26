<?php
// ============================================================
//  client/modifier_profil.php — Modification du profil
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
redirigerSiNonConnecte();

$pdo  = getPDO();
$stmt = $pdo->prepare(
    'SELECT id, nom, prenom, email, adresse, telephone, motDePasse
     FROM utilisateurs WHERE id = :id LIMIT 1'
);
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    rediriger(URL_BASE . '/auth/deconnexion.php');
}

$erreurs = [];
$valeurs = [
    'nom'       => $user['nom'],
    'prenom'    => $user['prenom'],
    'email'     => $user['email'],
    'adresse'   => $user['adresse'] ?? '',
    'telephone' => $user['telephone'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Token de sécurité invalide. Veuillez réessayer.';
    } else {
        $nom       = trim($_POST['nom']       ?? '');
        $prenom    = trim($_POST['prenom']     ?? '');
        $email     = trim($_POST['email']      ?? '');
        $adresse   = trim($_POST['adresse']    ?? '');
        $telephone = trim($_POST['telephone']  ?? '');
        $mdpActuel = $_POST['mdpActuel']       ?? '';
        $mdpNouv   = $_POST['mdpNouv']         ?? '';
        $mdpConf   = $_POST['mdpConf']         ?? '';

        $valeurs = compact('nom', 'prenom', 'email', 'adresse', 'telephone');

        // Validations de base
        if (empty($nom))    $erreurs[] = 'Le nom est obligatoire.';
        if (empty($prenom)) $erreurs[] = 'Le prénom est obligatoire.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'Adresse e-mail invalide.';
        }

        // Unicité email (exclure l'utilisateur courant)
        if (empty($erreurs)) {
            $stmtEmail = $pdo->prepare(
                'SELECT id FROM utilisateurs WHERE email = :email AND id != :id'
            );
            $stmtEmail->execute([':email' => $email, ':id' => $user['id']]);
            if ($stmtEmail->fetch()) {
                $erreurs[] = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
            }
        }

        // Changement de mot de passe (optionnel)
        $nouveauHash = null;
        if (!empty($mdpNouv)) {
            if (!password_verify($mdpActuel, $user['motDePasse'])) {
                $erreurs[] = 'Le mot de passe actuel est incorrect.';
            } elseif (strlen($mdpNouv) < 8) {
                $erreurs[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
            } elseif ($mdpNouv !== $mdpConf) {
                $erreurs[] = 'Les nouveaux mots de passe ne correspondent pas.';
            } else {
                $nouveauHash = password_hash($mdpNouv, PASSWORD_BCRYPT);
            }
        }

        if (empty($erreurs)) {
            if ($nouveauHash) {
                $stmtUp = $pdo->prepare(
                    'UPDATE utilisateurs
                     SET nom=:nom, prenom=:prenom, email=:email,
                         adresse=:adresse, telephone=:telephone, motDePasse=:mdp
                     WHERE id=:id'
                );
                $stmtUp->execute([
                    ':nom' => $nom, ':prenom' => $prenom, ':email' => $email,
                    ':adresse' => $adresse ?: null, ':telephone' => $telephone ?: null,
                    ':mdp' => $nouveauHash, ':id' => $user['id'],
                ]);
            } else {
                $stmtUp = $pdo->prepare(
                    'UPDATE utilisateurs
                     SET nom=:nom, prenom=:prenom, email=:email,
                         adresse=:adresse, telephone=:telephone
                     WHERE id=:id'
                );
                $stmtUp->execute([
                    ':nom' => $nom, ':prenom' => $prenom, ':email' => $email,
                    ':adresse' => $adresse ?: null, ':telephone' => $telephone ?: null,
                    ':id' => $user['id'],
                ]);
            }

            // Mise à jour de la session
            $_SESSION['user_nom']    = $nom;
            $_SESSION['user_prenom'] = $prenom;
            $_SESSION['user_email']  = $email;

            flashMessage('success', 'Profil mis à jour avec succès.');
            rediriger(URL_BASE . '/client/profil.php');
        }
    }
}

$titrePage = 'Modifier mon profil';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:580px;">
    <div class="d-flex align-items-center mb-4 gap-2">
        <a href="<?= URL_BASE ?>/client/profil.php" class="btn btn-link p-0 text-dark">
            <i class="bi bi-arrow-left fs-5"></i>
        </a>
        <h1 class="h3 mb-0">Modifier mon profil</h1>
    </div>

    <?php if ($erreurs): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($erreurs as $e): ?>
                    <li><?= h($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?= genererToken() ?>">

                <h6 class="text-uppercase small fw-semibold text-muted mb-3">Informations personnelles</h6>
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" id="prenom" name="prenom" class="form-control"
                               value="<?= h($valeurs['prenom']) ?>" required>
                    </div>
                    <div class="col-6">
                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="nom" name="nom" class="form-control"
                               value="<?= h($valeurs['nom']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= h($valeurs['email']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control"
                               value="<?= h($valeurs['telephone']) ?>">
                    </div>
                    <div class="col-12">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea id="adresse" name="adresse" class="form-control"
                                  rows="2"><?= h($valeurs['adresse']) ?></textarea>
                    </div>
                </div>

                <h6 class="text-uppercase small fw-semibold text-muted mb-3">
                    Changer le mot de passe <span class="fw-normal">(optionnel)</span>
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label for="mdpActuel" class="form-label">Mot de passe actuel</label>
                        <input type="password" id="mdpActuel" name="mdpActuel"
                               class="form-control" autocomplete="current-password">
                    </div>
                    <div class="col-12">
                        <label for="mdpNouv" class="form-label">Nouveau mot de passe</label>
                        <input type="password" id="mdpNouv" name="mdpNouv"
                               class="form-control" minlength="8" autocomplete="new-password">
                    </div>
                    <div class="col-12">
                        <label for="mdpConf" class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="mdpConf" name="mdpConf"
                               class="form-control" autocomplete="new-password">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">Enregistrer</button>
                    <a href="<?= URL_BASE ?>/client/profil.php" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
