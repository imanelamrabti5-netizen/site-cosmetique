<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonConnecte();

$pdo = getPDO();
$idClient = $_SESSION['user_id'];
$idCommande = (int) ($_GET['id'] ?? 0);

// Vérifier que la commande appartient bien au client connecté (pas d'accès via l'URL à une autre commande)
$stmt = $pdo->prepare("SELECT idCommande, dateCommande, montantTotal, statut
                        FROM commandes
                        WHERE idCommande = ? AND idClient = ?");
$stmt->execute([$idCommande, $idClient]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    flashMessage('danger', 'Commande introuvable.');
    rediriger(URL_BASE . '/commandes/historique.php');
}

// Lignes de la commande
$stmt = $pdo->prepare("SELECT lc.quantite, lc.prixUnitaire, p.idProduit, p.nom, p.image
                        FROM lignes_commande lc
                        JOIN produits p ON lc.idProduit = p.idProduit
                        WHERE lc.idCommande = ?");
$stmt->execute([$idCommande]);
$lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Infos de paiement associées (si déjà payée)
$stmt = $pdo->prepare("SELECT datePaiement, montant, modePaiement, statutPaiement
                        FROM paiements
                        WHERE idCommande = ?");
$stmt->execute([$idCommande]);
$paiement = $stmt->fetch(PDO::FETCH_ASSOC);

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
    <h1 class="mb-4">Suivi de la commande #<?= (int) $commande['idCommande'] ?></h1>

    <div class="row mb-4">
        <div class="col-md-6">
            <p><strong>Date :</strong> <?= h(date('d/m/Y à H:i', strtotime($commande['dateCommande']))) ?></p>
            <p><strong>Statut :</strong> <?= badgeStatutCommande($commande['statut']) ?></p>
            <p><strong>Montant total :</strong> <?= formaterPrix($commande['montantTotal']) ?></p>
        </div>
        <div class="col-md-6">
            <?php if ($paiement): ?>
                <p><strong>Paiement :</strong> <?= h($paiement['modePaiement']) ?> — <?= h($paiement['statutPaiement']) ?></p>
                <p><strong>Date de paiement :</strong> <?= h(date('d/m/Y à H:i', strtotime($paiement['datePaiement']))) ?></p>
            <?php else: ?>
                <p class="text-muted">Aucun paiement enregistré pour cette commande.</p>
                <?php if ($commande['statut'] === 'en attente'): ?>
                    <a href="<?= URL_BASE ?>/commandes/paiement.php?id=<?= (int) $commande['idCommande'] ?>" class="btn btn-primary btn-sm">
                        Procéder au paiement
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <h2 class="h5">Produits commandés</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Prix unitaire</th>
                    <th>Quantité</th>
                    <th>Sous-total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= URL_PRODUITS . h($ligne['image']) ?>" alt="<?= h($ligne['nom']) ?>"
                                     style="width:50px;height:50px;object-fit:cover;border-radius:4px;">
                                <a href="<?= URL_BASE ?>/produits/details.php?id=<?= (int) $ligne['idProduit'] ?>">
                                    <?= h($ligne['nom']) ?>
                                </a>
                            </div>
                        </td>
                        <td><?= formaterPrix($ligne['prixUnitaire']) ?></td>
                        <td><?= (int) $ligne['quantite'] ?></td>
                        <td><strong><?= formaterPrix($ligne['prixUnitaire'] * $ligne['quantite']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <a href="<?= URL_BASE ?>/commandes/historique.php" class="btn btn-outline-secondary">
        Retour à l'historique
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
