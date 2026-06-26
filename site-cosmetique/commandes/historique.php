<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonConnecte();

$pdo = getPDO();
$idClient = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT idCommande, dateCommande, montantTotal, statut
                        FROM commandes
                        WHERE idClient = ?
                        ORDER BY dateCommande DESC");
$stmt->execute([$idClient]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function badgeStatutCommande(string $statut): string
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
    <h1 class="mb-4">Historique de mes commandes</h1>

    <?php if (empty($commandes)): ?>
        <div class="alert alert-info">
            Vous n'avez passé aucune commande pour le moment.
            <a href="<?= URL_BASE ?>/produits/liste.php">Voir les produits</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>N° commande</th>
                        <th>Date</th>
                        <th>Montant total</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $commande): ?>
                        <tr>
                            <td>#<?= (int) $commande['idCommande'] ?></td>
                            <td><?= h(date('d/m/Y à H:i', strtotime($commande['dateCommande']))) ?></td>
                            <td><?= formaterPrix($commande['montantTotal']) ?></td>
                            <td><?= badgeStatutCommande($commande['statut']) ?></td>
                            <td>
                                <a href="<?= URL_BASE ?>/commandes/suivi.php?id=<?= (int) $commande['idCommande'] ?>"
                                   class="btn btn-sm btn-outline-secondary">Voir le détail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
