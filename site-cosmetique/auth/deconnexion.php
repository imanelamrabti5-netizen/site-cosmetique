<?php
// ============================================================
//  auth/deconnexion.php — Destruction de session
// ============================================================
require_once __DIR__ . '/../includes/functions.php';

// Destruction complète de la session
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

// Redirection vers l'accueil (pas de flashMessage car la session est détruite)
header('Location: ' . URL_BASE . '/index.php');
exit;
