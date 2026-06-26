<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonAdmin();

$pdo = getPDO();

$statutsValides = ['en attente', 'validée', 'expédiée', 'livrée', 'annulée'];

// --- Changer le statut d'une commande ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger', 'Requête invalide.');
        rediriger(URL_BASE . '/admin/commandes.php');
    }

    $idCommande = (int) ($_POST['idCommande'] ?? 0);
    $nouveauStatut = $_POST['statut'] ?? '';

    if (!in_array($nouveauStatut, $statutsValides, true)) {
        flashMessage('danger', 'Statut invalide.');
    } else {
        $stmt = $pdo->prepare("UPDATE commandes SET statut = ? WHERE idCommande = ?");
        $stmt->execute([$nouveauStatut, $idCommande]);
        flashMessage('success', 'Statut de la commande #' . $idCommande . ' mis à jour.');
    }

    rediriger(URL_BASE . '/admin/commandes.php' . (isset($_POST['filtreStatut']) && $_POST['filtreStatut'] !== ''
        ? '?statut=' . urlencode($_POST['filtreStatut']) : ''));
}

// --- Filtre par statut ---
$filtreStatut = $_GET['statut'] ?? '';

if ($filtreStatut !== '' && in_array($filtreStatut, $statutsValides, true)) {
    $sql = "SELECT c.idCommande, c.dateCommande, c.montantTotal, c.statut, u.nom, u.prenom, u.email
            FROM commandes c
            JOIN utilisateurs u ON c.idClient = u.id
            WHERE c.statut = ?
            ORDER BY c.dateCommande DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$filtreStatut]);
} else {
    $sql = "SELECT c.idCommande, c.dateCommande, c.montantTotal, c.statut, u.nom, u.prenom, u.email
            FROM commandes c
            JOIN utilisateurs u ON c.idClient = u.id
            ORDER BY c.dateCommande DESC";
    $stmt = $pdo->query($sql);
}
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function badgeStatutCommandeAdmin(string $statut): string
{
    $classes = [
        'en attente' => 'bg-secondary',
        'validée'    => 'bg-info text-dark',
        'expédiée'   => 'bg-primary',
        'livrée'     => 'bg-success',
        'annulée'    => 'bg-danger',
    ];
    $classe = $classes[$statut] ?? 'bg-secondary';
    return '<span class="badge ' . $classe . '">' . h($statut) . '</span>';
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Gestion des commandes</h1>

    <form action="<?= URL_BASE ?>/admin/commandes.php" method="get" class="row g-2 mb-4">
        <div class="col-md-4">
            <select name="statut" class="form-select" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <?php foreach ($statutsValides as $s): ?>
                    <option value="<?= h($s) ?>" <?= $filtreStatut === $s ? 'selected' : '' ?>><?= h(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Changer le statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commandes as $commande): ?>
                    <tr>
                        <td>#<?= (int) $commande['idCommande'] ?></td>
                        <td><?= h($commande['nom'] . ' ' . $commande['prenom']) ?><br>
                            <small class="text-muted"><?= h($commande['email']) ?></small>
                        </td>
                        <td><?= h(date('d/m/Y H:i', strtotime($commande['dateCommande']))) ?></td>
                        <td><?= formaterPrix($commande['montantTotal']) ?></td>
                        <td><?= badgeStatutCommandeAdmin($commande['statut']) ?></td>
                        <td>
                            <form action="<?= URL_BASE ?>/admin/commandes.php" method="post" class="d-flex gap-1">
                                <input type="hidden" name="csrf_token" value="<?= h(genererToken()) ?>">
                                <input type="hidden" name="idCommande" value="<?= (int) $commande['idCommande'] ?>">
                                <input type="hidden" name="filtreStatut" value="<?= h($filtreStatut) ?>">
                                <select name="statut" class="form-select form-select-sm">
                                    <?php foreach ($statutsValides as $s): ?>
                                        <option value="<?= h($s) ?>" <?= $commande['statut'] === $s ? 'selected' : '' ?>>
                                            <?= h(ucfirst($s)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">OK</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($commandes)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Aucune commande trouvée.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
