<?php
// ============================================================
//  includes/functions.php — Fonctions utilitaires & sécurité
//  À inclure en premier (après config.php et db.php) dans
//  toutes les pages du site.
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Démarrage de session sécurisé (une seule fois)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_DUREE,
        'path'     => '/',
        'secure'   => false,   // passer à true en HTTPS (production)
        'httponly' => true,    // inaccessible au JavaScript
        'samesite' => 'Lax',
    ]);
    session_start();
}


// ============================================================
//  1. VÉRIFICATIONS DE SESSION
// ============================================================

/**
 * Vérifie si un utilisateur est connecté (client ou admin).
 *
 * @return bool
 */
function estConnecte(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur connecté est administrateur.
 *
 * @return bool
 */
function estAdmin(): bool
{
    return estConnecte() && isset($_SESSION['user_role'])
           && $_SESSION['user_role'] === 'administrateur';
}

/**
 * Redirige vers la page de connexion si l'utilisateur n'est pas connecté.
 * À appeler en haut des pages réservées aux clients connectés.
 */
function redirigerSiNonConnecte(): void
{
    if (!estConnecte()) {
        header('Location: ' . URL_BASE . '/auth/connexion.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Redirige vers la page de connexion si l'utilisateur n'est pas admin.
 * À appeler en haut des pages du back-office.
 */
function redirigerSiNonAdmin(): void
{
    if (!estAdmin()) {
        header('Location: ' . URL_BASE . '/auth/connexion.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}


// ============================================================
//  2. PROTECTION XSS
// ============================================================

/**
 * Nettoie une valeur avant affichage (protection XSS).
 * Utilise htmlspecialchars avec ENT_QUOTES pour couvrir les attributs HTML.
 *
 * @param  mixed $valeur  Valeur à nettoyer (sera castée en string)
 * @return string
 */
function nettoyerEntree(mixed $valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Alias court pour nettoyerEntree() — pratique dans les templates HTML.
 *
 * @param  mixed $valeur
 * @return string
 */
function h(mixed $valeur): string
{
    return nettoyerEntree($valeur);
}


// ============================================================
//  3. PROTECTION CSRF
// ============================================================

/**
 * Génère (ou récupère) un token CSRF stocké en session.
 * Appeler dans chaque formulaire : <input type="hidden" name="csrf_token" value="<?= genererToken() ?>">
 *
 * @return string  Token hexadécimal de 64 caractères
 */
function genererToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF soumis via POST.
 * À appeler au début du traitement de chaque formulaire.
 *
 * @param  string $tokenSoumis  Valeur de $_POST['csrf_token']
 * @return bool
 */
function verifierToken(string $tokenSoumis): bool
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    // hash_equals() résiste aux attaques temporelles
    $valide = hash_equals($_SESSION['csrf_token'], $tokenSoumis);
    // Rotation du token après vérification (one-time use)
    unset($_SESSION['csrf_token']);
    return $valide;
}


// ============================================================
//  4. HELPERS DIVERS
// ============================================================

/**
 * Formate un prix en dirhams avec 2 décimales et le symbole €.
 *
 * @param  float|string $prix
 * @return string  Ex. : "29,90 MAD"
 */
function formaterPrix(float|string $prix): string
{
    return number_format((float) $prix, 2, ',', ' ') . ' MAD';
}

/**
 * Redirige vers une URL et termine le script.
 *
 * @param string $url
 */
function rediriger(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Ajoute un message flash en session (succès, erreur, info).
 * Affiché une seule fois par includes/header.php.
 *
 * @param string $type     'success' | 'danger' | 'warning' | 'info'
 * @param string $message
 */
function flashMessage(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Récupère et vide les messages flash de la session.
 *
 * @return array<int, array{type: string, message: string}>
 */
function getFlashMessages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Retourne le nombre d'articles dans le panier de l'utilisateur connecté.
 * Utilisé par la navbar pour afficher le badge.
 *
 * @return int
 */
function nombreArticlesPanier(): int
{
    if (!estConnecte()) {
        return 0;
    }
    try {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(lp.quantite), 0)
             FROM paniers p
             JOIN lignes_panier lp ON lp.idPanier = p.idPanier
             WHERE p.idClient = :idClient'
        );
        $stmt->execute([':idClient' => $_SESSION['user_id']]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

/**
 * Retourne le nombre de favoris de l'utilisateur connecté.
 *
 * @return int
 */
function nombreFavoris(): int
{
    if (!estConnecte()) {
        return 0;
    }
    try {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM favoris WHERE idClient = :idClient'
        );
        $stmt->execute([':idClient' => $_SESSION['user_id']]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}
