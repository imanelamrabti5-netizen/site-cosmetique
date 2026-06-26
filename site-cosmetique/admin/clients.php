<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonAdmin();

$pdo = getPDO();

// --- Activer / désactiver un compte ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_actif') {
    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger', 'Requête invalide.');
        rediriger(URL_BASE . '/admin/clients.php');
    }

    $idUtilisateur = (int) ($_POST['idUtilisateur'] ?? 0);

    $stmt = $pdo->prepare("SELECT actif FROM utilisateurs WHERE id = ? AND role = 'client'");
    $stmt->execute([$idUtilisateur]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($client) {
        $nouvelEtat = $client['actif'] ? 0 : 1;
        $pdo->prepare("UPDATE utilisateurs SET actif = ? WHERE id = ?")->execute([$nouvelEtat, $idUtilisateur]);
        flashMessage('success', $nouvelEtat ? 'Compte réactivé.' : 'Compte désactivé.');
    } else {
        flashMessage('danger', 'Client introuvable.');
    }

    rediriger(URL_BASE . '/admin/clients.php' . (isset($_POST['retourId']) ? '?id=' . (int) $_POST['retourId'] : ''));
}

// --- Recherche ---
$recherche = trim($_GET['recherche'] ?? '');

if ($recherche !== '') {
    $sql = "SELECT id, nom, prenom, email, telephone, dateInscription, actif
            FROM utilisateurs
            WHERE role = 'client' AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)
            ORDER BY dateInscription DESC";
    $stmt = $pdo->prepare($sql);
    $like = '%' . $recherche . '%';
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT id, nom, prenom, email, telephone, dateInscription, actif
                          FROM utilisateurs
                          WHERE role = 'client'
                          ORDER BY dateInscription DESC");
}
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Vue détail d'un client ---
$clientDetail = null;
$statsClientDetail = null;
$idDetail = (int) ($_GET['id'] ?? 0);

if ($idDetail > 0) {
    $stmt = $pdo->prepare("SELECT id, nom, prenom, email, adresse, telephone, dateInscription, actif
                            FROM utilisateurs WHERE id = ? AND role = 'client'");
    $stmt->execute([$idDetail]);
    $clientDetail = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($clientDetail) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE idClient = ?");
        $stmt->execute([$idDetail]);
        $nbCommandes = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE idClient = ?");
        $stmt->execute([$idDetail]);
        $nbFavoris = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM avis WHERE idClient = ?");
        $stmt->execute([$idDetail]);
        $nbAvis = (int) $stmt->fetchColumn();

        $statsClientDetail = ['commandes' => $nbCommandes, 'favoris' => $nbFavoris, 'avis' => $nbAvis];
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Gestion des clients</h1>

    <?php if ($clientDetail): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Détail du client</strong>
                <a href="<?= URL_BASE ?>/admin/clients.php" class="btn btn-sm btn-outline-secondary">Fermer</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nom :</strong> <?= h($clientDetail['nom'] . ' ' . $clientDetail['prenom']) ?></p>
                        <p><strong>Email :</strong> <?= h($clientDetail['email']) ?></p>
                        <p><strong>Téléphone :</strong> <?= h($clientDetail['telephone'] ?: 'Non renseigné') ?></p>
                        <p><strong>Adresse :</strong> <?= h($clientDetail['adresse'] ?: 'Non renseignée') ?></p>
                        <p><strong>Inscrit le :</strong> <?= h(date('d/m/Y', strtotime($clientDetail['dateInscription']))) ?></p>
                        <p>
                            <strong>Statut :</strong>
                            <?= $clientDetail['actif']
                                ? '<span class="badge bg-success">Actif</span>'
                                : '<span class="badge bg-danger">Désactivé</span>' ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Commandes passées :</strong> <?= $statsClientDetail['commandes'] ?></p>
                        <p><strong>Produits favoris :</strong> <?= $statsClientDetail['favoris'] ?></p>
                        <p><strong>Avis laissés :</strong> <?= $statsClientDetail['avis'] ?></p>
                    </div>
                </div>
                <form action="<?= URL_BASE ?>/admin/clients.php" method="post" class="mt-2">
                    <input type="hidden" name="csrf_token" value="<?= h(genererToken()) ?>">
                    <input type="hidden" name="action" value="toggle_actif">
                    <input type="hidden" name="idUtilisateur" value="<?= (int) $clientDetail['id'] ?>">
                    <input type="hidden" name="retourId" value="<?= (int) $clientDetail['id'] ?>">
                    <button type="submit" class="btn btn-sm <?= $clientDetail['actif'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                        <?= $clientDetail['actif'] ? 'Désactiver ce compte' : 'Réactiver ce compte' ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <form action="<?= URL_BASE ?>/admin/clients.php" method="get" class="row g-2 mb-4">
        <div class="col-md-8">
            <input type="text" name="recherche" class="form-control" placeholder="Rechercher par nom ou email..."
                   value="<?= h($recherche) ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Rechercher</button>
        </div>
        <?php if ($recherche !== ''): ?>
            <div class="col-md-2">
                <a href="<?= URL_BASE ?>/admin/clients.php" class="btn btn-outline-secondary w-100">Réinitialiser</a>
            </div>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Inscrit le</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?= h($client['nom'] . ' ' . $client['prenom']) ?></td>
                        <td><?= h($client['email']) ?></td>
                        <td><?= h(date('d/m/Y', strtotime($client['dateInscription']))) ?></td>
                        <td>
                            <?= $client['actif']
                                ? '<span class="badge bg-success">Actif</span>'
                                : '<span class="badge bg-danger">Désactivé</span>' ?>
                        </td>
                        <td>
                            <a href="<?= URL_BASE ?>/admin/clients.php?id=<?= (int) $client['id'] ?>"
                               class="btn btn-sm btn-outline-secondary">Voir détail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($clients)): ?>
                    <tr><td colspan="5" class="text-center text-muted">Aucun client trouvé.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
