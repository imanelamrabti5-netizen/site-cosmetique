<?php
// ============================================================
//  includes/header.php
//  À inclure tout en haut de chaque page (avant tout echo).
//  Charge les dépendances, démarre la session, affiche
//  le <head> HTML et les messages flash.
// ============================================================

require_once __DIR__ . '/functions.php';

// Titre de page dynamique : chaque page peut définir $titrePage avant l'include
$titrePage = isset($titrePage) ? h($titrePage) . ' — ' . SITE_NOM : SITE_NOM;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titrePage ?></title>

    <!-- Bootstrap 5 CDN -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts : Lora (titres) + Inter (corps) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap"
          rel="stylesheet">

    <!-- CSS personnalisé (sera rempli lors du Prompt 8) -->
    <link rel="stylesheet" href="<?= URL_CSS ?>/style.css">
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<!-- Messages flash -->
<?php foreach (getFlashMessages() as $flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= h($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
</div>
<?php endforeach; ?>

<!-- Contenu principal -->
<main class="py-4">
