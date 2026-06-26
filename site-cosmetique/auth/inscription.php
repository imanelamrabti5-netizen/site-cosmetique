<?php
// ============================================================
//  auth/inscription.php — Création de compte client
// ============================================================
require_once __DIR__ . '/../includes/functions.php';

// Déjà connecté → accueil
if (estConnecte()) {
    rediriger(URL_BASE . '/index.php');
}

$erreurs = [];
$valeurs = ['nom' => '', 'prenom' => '', 'email' => '', 'adresse' => '', 'telephone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérification CSRF
    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Token de sécurité invalide. Veuillez réessayer.';
    } else {
        // Récupération & nettoyage des champs
        $nom       = trim($_POST['nom']       ?? '');
        $prenom    = trim($_POST['prenom']     ?? '');
        $email     = trim($_POST['email']      ?? '');
        $mdp       = $_POST['motDePasse']      ?? '';
        $mdpConf   = $_POST['motDePasseConf']  ?? '';
        $adresse   = trim($_POST['adresse']    ?? '');
        $telephone = trim($_POST['telephone']  ?? '');

        $valeurs = compact('nom', 'prenom', 'email', 'adresse', 'telephone');

        // Validations
        if (empty($nom))    $erreurs[] = 'Le nom est obligatoire.';
        if (empty($prenom)) $erreurs[] = 'Le prénom est obligatoire.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'Adresse e-mail invalide.';
        }
        if (strlen($mdp) < 8) {
            $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        if ($mdp !== $mdpConf) {
            $erreurs[] = 'Les mots de passe ne correspondent pas.';
        }

        if (empty($erreurs)) {
            $pdo = getPDO();

            // Unicité de l'email
            $stmtCheck = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = :email');
            $stmtCheck->execute([':email' => $email]);
            if ($stmtCheck->fetch()) {
                $erreurs[] = 'Cette adresse e-mail est déjà utilisée.';
            } else {
                // Insertion
                $hash = password_hash($mdp, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare(
                    'INSERT INTO utilisateurs
                        (nom, prenom, email, motDePasse, role, adresse, telephone)
                     VALUES
                        (:nom, :prenom, :email, :mdp, "client", :adresse, :telephone)'
                );
                $stmt->execute([
                    ':nom'       => $nom,
                    ':prenom'    => $prenom,
                    ':email'     => $email,
                    ':mdp'       => $hash,
                    ':adresse'   => $adresse ?: null,
                    ':telephone' => $telephone ?: null,
                ]);

                flashMessage('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');
                rediriger(URL_BASE . '/auth/connexion.php');
            }
        }
    }
}

$titrePage = 'Créer un compte';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:540px;">
    <h1 class="h3 mb-4 text-center">Créer un compte</h1>

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

                <div class="row g-3">
                    <div class="col-6">
                        <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" id="prenom" name="prenom" class="form-control"
                               value="<?= h($valeurs['prenom']) ?>" required autocomplete="given-name">
                    </div>
                    <div class="col-6">
                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="nom" name="nom" class="form-control"
                               value="<?= h($valeurs['nom']) ?>" required autocomplete="family-name">
                    </div>
                    <div class="col-12">
                        <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= h($valeurs['email']) ?>" required autocomplete="email">
                    </div>
                    <div class="col-12">
                        <label for="motDePasse" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                        <input type="password" id="motDePasse" name="motDePasse"
                               class="form-control" required autocomplete="new-password"
                               minlength="8">
                        <div class="form-text">8 caractères minimum.</div>
                    </div>
                    <div class="col-12">
                        <label for="motDePasseConf" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                        <input type="password" id="motDePasseConf" name="motDePasseConf"
                               class="form-control" required autocomplete="new-password">
                    </div>
                    <div class="col-12">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea id="adresse" name="adresse" class="form-control"
                                  rows="2" autocomplete="street-address"><?= h($valeurs['adresse']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control"
                               value="<?= h($valeurs['telephone']) ?>" autocomplete="tel">
                    </div>
                </div>

                <button type="submit" class="btn btn-dark w-100 mt-4">Créer mon compte</button>
            </form>
        </div>
    </div>

    <p class="text-center mt-3 small">
        Déjà un compte ?
        <a href="<?= URL_BASE ?>/auth/connexion.php">Se connecter</a>
    </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
