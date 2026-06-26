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
$quantiteDemandee = max(1, (int) ($_POST['quantite'] ?? 1));

// Vérifier que le produit existe
$stmt = $pdo->prepare("SELECT idProduit, nom, stock FROM produits WHERE idProduit = ?");
$stmt->execute([$idProduit]);
$produit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produit) {
    flashMessage('danger', 'Produit introuvable.');
    rediriger(URL_BASE . '/produits/liste.php');
}

// Récupérer ou créer le panier du client
$stmt = $pdo->prepare("SELECT idPanier FROM paniers WHERE idClient = ?");
$stmt->execute([$idClient]);
$panier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$panier) {
    $stmt = $pdo->prepare("INSERT INTO paniers (idClient, total, dateMaj) VALUES (?, 0, NOW())");
    $stmt->execute([$idClient]);
    $idPanier = (int) $pdo->lastInsertId();
} else {
    $idPanier = (int) $panier['idPanier'];
}

// Vérifier si une ligne existe déjà pour ce produit dans ce panier
$stmt = $pdo->prepare("SELECT idLignePanier, quantite FROM lignes_panier WHERE idPanier = ? AND idProduit = ?");
$stmt->execute([$idPanier, $idProduit]);
$ligneExistante = $stmt->fetch(PDO::FETCH_ASSOC);

$quantiteFinale = $quantiteDemandee + ($ligneExistante['quantite'] ?? 0);

if ($quantiteFinale > $produit['stock']) {
    flashMessage('warning', 'Stock insuffisant pour "' . $produit['nom'] . '" (stock disponible : ' . $produit['stock'] . ').');
    rediriger(URL_BASE . '/produits/details.php?id=' . $idProduit);
}

if ($ligneExistante) {
    $stmt = $pdo->prepare("UPDATE lignes_panier SET quantite = ? WHERE idLignePanier = ?");
    $stmt->execute([$quantiteFinale, $ligneExistante['idLignePanier']]);
} else {
    $stmt = $pdo->prepare("INSERT INTO lignes_panier (idPanier, idProduit, quantite, sousTotal) VALUES (?, ?, ?, 0)");
    $stmt->execute([$idPanier, $idProduit, $quantiteFinale]);
}

$pdo->prepare("UPDATE paniers SET dateMaj = NOW() WHERE idPanier = ?")->execute([$idPanier]);

flashMessage('success', '"' . $produit['nom'] . '" ajouté au panier.');
rediriger(URL_BASE . '/panier/panier.php');
