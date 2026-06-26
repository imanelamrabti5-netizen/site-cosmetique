<?php
// ============================================================
//  client/profil.php — Profil du client connecté
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
redirigerSiNonConnecte();

$pdo  = getPDO();
$stmt = $pdo->prepare(
    'SELECT id, nom, prenom, email, adresse, telephone, dateInscription
     FROM utilisateurs WHERE id = :id LIMIT 1'
);
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    flashMessage('danger', 'Utilisateur introuvable.');
    rediriger(URL_BASE . '/auth/deconnexion.php');
}

// Récupération du nombre de commandes et d'avis
$stmtStats = $pdo->prepare(
    'SELECT
        (SELECT COUNT(*) FROM commandes WHERE idClient = :id1) AS nbCommandes,
        (SELECT COUNT(*) FROM avis      WHERE idClient = :id2) AS nbAvis,
        (SELECT COUNT(*) FROM favoris   WHERE idClient = :id3) AS nbFavoris'
);
$stmtStats->execute([':id1' => $user['id'], ':id2' => $user['id'], ':id3' => $user['id']]);
$stats = $stmtStats->fetch();

$titrePage = 'Mon profil';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:700px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Mon profil</h1>
        <a href="<?= URL_BASE ?>/client/modifier_profil.php" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-pencil me-1"></i>Modifier
        </a>
    </div>

    <!-- Carte infos personnelles -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Informations personnelles</h5>
            <dl class="row mb-0">
                <dt class="col-sm-4">Prénom</dt>
                <dd class="col-sm-8"><?= h($user['prenom']) ?></dd>

                <dt class="col-sm-4">Nom</dt>
                <dd class="col-sm-8"><?= h($user['nom']) ?></dd>

                <dt class="col-sm-4">E-mail</dt>
                <dd class="col-sm-8"><?= h($user['email']) ?></dd>

                <dt class="col-sm-4">Téléphone</dt>
                <dd class="col-sm-8"><?= h($user['telephone'] ?? '—') ?></dd>

                <dt class="col-sm-4">Adresse</dt>
                <dd class="col-sm-8"><?= nl2br(h($user['adresse'] ?? '—')) ?></dd>

                <dt class="col-sm-4">Membre depuis</dt>
                <dd class="col-sm-8">
                    <?= h(date('d/m/Y', strtotime($user['dateInscription']))) ?>
                </dd>
            </dl>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row g-3 text-center">
        <div class="col-4">
            <div class="card shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold"><?= (int) $stats['nbCommandes'] ?></div>
                    <div class="small text-muted">Commandes</div>
                    <a href="<?= URL_BASE ?>/commandes/historique.php"
                       class="stretched-link"></a>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold"><?= (int) $stats['nbFavoris'] ?></div>
                    <div class="small text-muted">Favoris</div>
                    <a href="<?= URL_BASE ?>/favoris/favoris.php"
                       class="stretched-link"></a>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold"><?= (int) $stats['nbAvis'] ?></div>
                    <div class="small text-muted">Avis déposés</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
