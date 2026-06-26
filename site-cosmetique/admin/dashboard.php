<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonAdmin();

$pdo = getPDO();

$nbCommandesEnAttente = (int) $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'en attente'")->fetchColumn();
$nbClients = (int) $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'client'")->fetchColumn();
$nbRuptureStock = (int) $pdo->query("SELECT COUNT(*) FROM produits WHERE stock <= 0")->fetchColumn();
$nbCommandesTotal = (int) $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Tableau de bord administrateur</h1>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bi bi-hourglass-split fs-1"></i>
                    <h2 class="h4 mt-2"><?= $nbCommandesEnAttente ?></h2>
                    <p class="text-muted mb-0">Commandes en attente</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bi bi-bag-check fs-1"></i>
                    <h2 class="h4 mt-2"><?= $nbCommandesTotal ?></h2>
                    <p class="text-muted mb-0">Commandes au total</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bi bi-people fs-1"></i>
                    <h2 class="h4 mt-2"><?= $nbClients ?></h2>
                    <p class="text-muted mb-0">Clients inscrits</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bi bi-exclamation-triangle fs-1"></i>
                    <h2 class="h4 mt-2"><?= $nbRuptureStock ?></h2>
                    <p class="text-muted mb-0">Produits en rupture de stock</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <a href="<?= URL_BASE ?>/admin/produits.php" class="btn btn-outline-secondary w-100 py-3">
                <i class="bi bi-box-seam"></i> Gérer les produits
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= URL_BASE ?>/admin/categories.php" class="btn btn-outline-secondary w-100 py-3">
                <i class="bi bi-tags"></i> Gérer les catégories
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= URL_BASE ?>/admin/clients.php" class="btn btn-outline-secondary w-100 py-3">
                <i class="bi bi-people"></i> Gérer les clients
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= URL_BASE ?>/admin/commandes.php" class="btn btn-outline-secondary w-100 py-3">
                <i class="bi bi-bag-check"></i> Gérer les commandes
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= URL_BASE ?>/admin/promotions.php" class="btn btn-outline-secondary w-100 py-3">
                <i class="bi bi-percent"></i> Gérer les promotions
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= URL_BASE ?>/admin/statistiques.php" class="btn btn-outline-secondary w-100 py-3">
                <i class="bi bi-graph-up"></i> Voir les statistiques
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
