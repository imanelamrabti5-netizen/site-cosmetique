<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonConnecte();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierToken($_POST['csrf_token'] ?? '')) {
    flashMessage('danger', 'Requête invalide.');
    rediriger(URL_BASE . '/produits/liste.php');
}

$pdo = getPDO();
$idClient = $_SESSION['user_id'];
$idProduit = (int) ($_POST['idProduit'] ?? 0);

// Vérifier que le produit existe
$stmt = $pdo->prepare("SELECT idProduit, nom FROM produits WHERE idProduit = ?");
$stmt->execute([$idProduit]);
$produit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produit) {
    flashMessage('danger', 'Produit introuvable.');
    rediriger(URL_BASE . '/produits/liste.php');
}

// Empêcher les doublons
$stmt = $pdo->prepare("SELECT idFavori FROM favoris WHERE idClient = ? AND idProduit = ?");
$stmt->execute([$idClient, $idProduit]);

if ($stmt->fetch()) {
    flashMessage('info', 'Ce produit est déjà dans vos favoris.');
} else {
    $stmt = $pdo->prepare("INSERT INTO favoris (idClient, idProduit, dateAjout) VALUES (?, ?, NOW())");
    $stmt->execute([$idClient, $idProduit]);
    flashMessage('success', '"' . $produit['nom'] . '" ajouté à vos favoris.');
}

$retour = $_POST['redirect'] ?? (URL_BASE . '/produits/details.php?id=' . $idProduit);
rediriger($retour);
