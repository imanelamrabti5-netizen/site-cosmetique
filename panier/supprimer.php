<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonConnecte();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierToken($_POST['csrf_token'] ?? '')) {
    flashMessage('danger', 'Requête invalide.');
    rediriger(URL_BASE . '/panier/panier.php');
}

$pdo = getPDO();
$idClient = $_SESSION['user_id'];
$idLignePanier = (int) ($_POST['idLignePanier'] ?? 0);

// Supprimer uniquement si la ligne appartient bien au panier du client connecté
$sql = "DELETE lp FROM lignes_panier lp
        JOIN paniers pa ON lp.idPanier = pa.idPanier
        WHERE lp.idLignePanier = ? AND pa.idClient = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$idLignePanier, $idClient]);

if ($stmt->rowCount() > 0) {
    flashMessage('success', 'Produit retiré du panier.');
} else {
    flashMessage('warning', 'Impossible de retirer ce produit.');
}

rediriger(URL_BASE . '/panier/panier.php');
