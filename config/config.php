<?php
// ============================================================
//  config/config.php — Constantes globales du site
// ============================================================

// --- Identité du site ---
define('SITE_NOM',        'Maison Lumière');
define('SITE_SLOGAN',     'La beauté naturelle, sublimée.');
define('SITE_EMAIL',      'contact@maisonlumiere.fr');

// --- URL de base (à adapter selon votre config XAMPP) ---
// Exemple avec XAMPP local : http://localhost/site-cosmetique
define('URL_BASE',        'http://localhost/site-cosmetique');

// --- Chemins absolus (utiles pour les includes côté serveur) ---
define('CHEMIN_RACINE',   dirname(__DIR__));          // /path/to/site-cosmetique
define('CHEMIN_INCLUDES', CHEMIN_RACINE . '/includes');
define('CHEMIN_ASSETS',   CHEMIN_RACINE . '/assets');

// --- Chemins URL des assets ---
define('URL_CSS',         URL_BASE . '/assets/css');
define('URL_JS',          URL_BASE . '/assets/js');
define('URL_IMAGES',      URL_BASE . '/assets/images');
define('URL_PRODUITS',    URL_BASE . '/assets/images/produits/');

// --- Dossier d'upload des images produits ---
define('UPLOAD_DIR',      CHEMIN_RACINE . '/assets/images/produits/');

// --- Pagination ---
define('PRODUITS_PAR_PAGE', 12);

// --- Session ---
define('SESSION_DUREE',   3600); // 1 heure en secondes

// --- Environnement ---
// Mettre à false en production
define('MODE_DEBUG', true);

if (MODE_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
