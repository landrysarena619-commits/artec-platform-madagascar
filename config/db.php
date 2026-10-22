<?php
/**
 * Modèle de connexion à la base de données.
 * À COPIER en `db.php` et à adapter avec tes identifiants locaux.
 */

// Paramètres LOCAL (WAMP)
define('DB_HOST', 'localhost');
define('DB_NAME', 'artec_platform');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Paramètres PRODUCTION (o2switch ou autre)
// Décommenter et adapter quand tu passeras en prod
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'utilisateur_artec');
// define('DB_USER', 'utilisateur_artec');
// define('DB_PASS', 'mot_de_passe_fort');

/**
 * Retourne la connexion PDO unique (singleton).
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_ENV === 'local') {
                die('Erreur BDD : ' . $e->getMessage());
            }
            error_log('DB Error: ' . $e->getMessage());
            die('Une erreur technique est survenue. Veuillez réessayer plus tard.');
        }
    }
    return $pdo;
}