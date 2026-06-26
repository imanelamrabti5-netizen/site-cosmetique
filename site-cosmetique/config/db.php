<?php
// ============================================================
//  config/db.php — Connexion à MySQL via PDO
// ============================================================

require_once __DIR__ . '/config.php';

// --- Paramètres de connexion ---
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NOM',     'cosmetique_db');
define('DB_CHARSET', 'utf8mb4');
define('DB_USER',    'root');       // utilisateur XAMPP par défaut
define('DB_PASS',    '');           // mot de passe XAMPP par défaut (vide)

/**
 * Retourne une instance PDO partagée (pattern Singleton léger).
 * Toutes les pages appellent getPDO() — une seule connexion par requête HTTP.
 *
 * @return PDO
 * @throws RuntimeException si la connexion échoue
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NOM,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // lève des PDOException
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // tableaux associatifs par défaut
            PDO::ATTR_EMULATE_PREPARES   => false,                    // vraies requêtes préparées
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En production : loguer l'erreur sans l'afficher
            if (MODE_DEBUG) {
                throw new RuntimeException(
                    'Erreur de connexion à la base de données : ' . $e->getMessage(),
                    (int) $e->getCode(),
                    $e
                );
            } else {
                // Message générique pour l'utilisateur final
                die('Une erreur technique est survenue. Veuillez réessayer plus tard.');
            }
        }
    }

    return $pdo;
}
