<?php
// ============================================================
//  auth/connexion.php — Connexion utilisateur
// ============================================================
require_once __DIR__ . '/../includes/functions.php';

// Déjà connecté → redirection selon le rôle
if (estConnecte()) {
    rediriger(estAdmin() ? URL_BASE . '/admin/dashboard.php' : URL_BASE . '/index.php');
}

$erreur  = '';
$email   = '';
// URL de redirection après connexion (passée en query string par redirigerSiNonConnecte)
$redirect = filter_var($_GET['redirect'] ?? '', FILTER_SANITIZE_URL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        $erreur = 'Token de sécurité invalide. Veuillez réessayer.';
    } else {
        $email = trim($_POST['email']      ?? '');
        $mdp   = $_POST['motDePasse']      ?? '';
        $redirect = filter_var($_POST['redirect'] ?? '', FILTER_SANITIZE_URL);

        if (empty($email) || empty($mdp)) {
            $erreur = 'Veuillez remplir tous les champs.';
        } else {
            $pdo  = getPDO();
            $stmt = $pdo->prepare(
                'SELECT id, nom, prenom, email, motDePasse, role, actif
                 FROM utilisateurs
                 WHERE email = :email
                 LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($mdp, $user['motDePasse'])) {
                $erreur = 'E-mail ou mot de passe incorrect.';
            } elseif (!$user['actif']) {
                $erreur = 'Ce compte a été désactivé. Contactez le support.';
            } else {
                // Régénération de l'ID de session (prévention fixation)
                session_regenerate_id(true);

                $_SESSION['user_id']     = $user['id'];
                $_SESSION['user_nom']    = $user['nom'];
                $_SESSION['user_prenom'] = $user['prenom'];
                $_SESSION['user_email']  = $user['email'];
                $_SESSION['user_role']   = $user['role'];

                flashMessage('success', 'Bienvenue, ' . $user['prenom'] . ' !');

                if ($user['role'] === 'administrateur') {
                    rediriger(URL_BASE . '/admin/dashboard.php');
                } elseif (!empty($redirect) && str_starts_with($redirect, '/')) {
                    rediriger(URL_BASE . $redirect);
                } else {
                    rediriger(URL_BASE . '/index.php');
                }
            }
        }
    }
}

$titrePage = 'Connexion';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:440px;">
    <h1 class="h3 mb-4 text-center">Connexion</h1>

    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= h($erreur) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?= genererToken() ?>">
                <input type="hidden" name="redirect"   value="<?= h($redirect) ?>">

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= h($email) ?>" required autofocus autocomplete="email">
                </div>
                <div class="mb-3">
                    <label for="motDePasse" class="form-label">Mot de passe</label>
                    <input type="password" id="motDePasse" name="motDePasse"
                           class="form-control" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-dark w-100 mt-2">Se connecter</button>
            </form>
        </div>
    </div>

    <p class="text-center mt-3 small">
        Pas encore de compte ?
        <a href="<?= URL_BASE ?>/auth/inscription.php">Créer un compte</a>
    </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
