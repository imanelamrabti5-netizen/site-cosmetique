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
$quantite = max(1, (int) ($_POST['quantite'] ?? 1));

// Vérifier que la ligne appartient bien au panier du client connecté
$sql = "SELECT lp.idLignePanier, p.stock, p.prix, p.nom
        FROM lignes_panier lp
        JOIN paniers pa ON lp.idPanier = pa.idPanier
        JOIN produits p ON lp.idProduit = p.idProduit
        WHERE lp.idLignePanier = ? AND pa.idClient = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$idLignePanier, $idClient]);
$ligne = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ligne) {
    flashMessage('danger', 'Ligne de panier introuvable.');
    rediriger(URL_BASE . '/panier/panier.php');
}

if ($quantite > $ligne['stock']) {
    flashMessage('warning', 'Stock insuffisant pour "' . $ligne['nom'] . '" (stock disponible : ' . $ligne['stock'] . ').');
    rediriger(URL_BASE . '/panier/panier.php');
}

// Le sous-total exact (avec promo éventuelle) est recalculé à l'affichage par panier.php
$sousTotal = $ligne['prix'] * $quantite;
$stmt = $pdo->prepare("UPDATE lignes_panier SET quantite = ?, sousTotal = ? WHERE idLignePanier = ?");
$stmt->execute([$quantite, $sousTotal, $idLignePanier]);

flashMessage('success', 'Quantité mise à jour.');
rediriger(URL_BASE . '/panier/panier.php');
