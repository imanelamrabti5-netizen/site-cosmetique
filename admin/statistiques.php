<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonAdmin();

$pdo = getPDO();

// Chiffre d'affaires total (commandes validées, expédiées ou livrées = payées)
$stmt = $pdo->query("SELECT COALESCE(SUM(montantTotal), 0)
                      FROM commandes
                      WHERE statut IN ('validée', 'expédiée', 'livrée')");
$caTotal = (float) $stmt->fetchColumn();

// Chiffre d'affaires par mois
$sql = "SELECT DATE_FORMAT(dateCommande, '%Y-%m') AS mois, SUM(montantTotal) AS total
        FROM commandes
        WHERE statut IN ('validée', 'expédiée', 'livrée')
        GROUP BY mois
        ORDER BY mois DESC";
$caParMois = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Top 5 des produits les plus vendus (quantité cumulée)
$sql = "SELECT p.nom, SUM(lc.quantite) AS quantiteVendue
        FROM lignes_commande lc
        JOIN produits p ON lc.idProduit = p.idProduit
        GROUP BY lc.idProduit, p.nom
        ORDER BY quantiteVendue DESC
        LIMIT 5";
$topProduits = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Nombre de commandes par statut
$sql = "SELECT statut, COUNT(*) AS nb FROM commandes GROUP BY statut";
$commandesParStatut = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Statistiques de vente</h1>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Chiffre d'affaires total</p>
                    <h2 class="h3"><?= formaterPrix($caTotal) ?></h2>
                    <small class="text-muted">Commandes validées, expédiées ou livrées</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <h2 class="h5">Chiffre d'affaires par mois</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Chiffre d'affaires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($caParMois as $ligne): ?>
                            <tr>
                                <td><?= h($ligne['mois']) ?></td>
                                <td><?= formaterPrix($ligne['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($caParMois)): ?>
                            <tr><td colspan="2" class="text-center text-muted">Aucune donnée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-md-6">
            <h2 class="h5">Top 5 des produits les plus vendus</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Quantité vendue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProduits as $produit): ?>
                            <tr>
                                <td><?= h($produit['nom']) ?></td>
                                <td><?= (int) $produit['quantiteVendue'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($topProduits)): ?>
                            <tr><td colspan="2" class="text-center text-muted">Aucune vente enregistrée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-md-6">
            <h2 class="h5">Commandes par statut</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Statut</th>
                            <th>Nombre de commandes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandesParStatut as $ligne): ?>
                            <tr>
                                <td><?= h(ucfirst($ligne['statut'])) ?></td>
                                <td><?= (int) $ligne['nb'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($commandesParStatut)): ?>
                            <tr><td colspan="2" class="text-center text-muted">Aucune commande.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
