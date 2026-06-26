<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonConnecte();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierToken($_POST['csrf_token'] ?? '')) {
    flashMessage('danger', 'Requête invalide.');
    rediriger(URL_BASE . '/favoris/favoris.php');
}

$pdo = getPDO();
$idClient = $_SESSION['user_id'];
$idProduit = (int) ($_POST['idProduit'] ?? 0);

$stmt = $pdo->prepare("DELETE FROM favoris WHERE idClient = ? AND idProduit = ?");
$stmt->execute([$idClient, $idProduit]);

if ($stmt->rowCount() > 0) {
    flashMessage('success', 'Produit retiré de vos favoris.');
} else {
    flashMessage('warning', "Ce produit n'était pas dans vos favoris.");
}

$retour = $_POST['redirect'] ?? (URL_BASE . '/favoris/favoris.php');
rediriger($retour);
