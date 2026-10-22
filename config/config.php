<?php
/**
 * Configuration globale de la plateforme ARTEC Madagascar
 * Chargé en premier sur chaque page.
 */

// --- Environnement ---
define('APP_ENV', 'local'); // 'local' ou 'production'
define('APP_NAME', 'ARTEC Madagascar');
define('APP_VERSION', '1.0.0');

// --- URL de base (détection auto) ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
// Recalcule depuis la racine du projet (dossier parent des dossiers d'espaces)
$projectRoot = '/artec-platform-madagascar';
define('BASE_URL', $protocol . '://' . $host . $projectRoot . '/');

// --- Chemins ---
define('ROOT_PATH', dirname(__DIR__));
define('ASSETS_URL', BASE_URL . 'assets/');

// --- Timezone Madagascar ---
date_default_timezone_set('Indian/Antananarivo');

// --- Gestion des erreurs ---
if (APP_ENV === 'local') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

// --- Session sécurisée ---
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (APP_ENV === 'production'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('ARTECSESSID');
    session_start();
}

// --- Token visiteur (cookie 1 an) pour lier avis et chat ---
if (empty($_COOKIE['visitor_token'])) {
    $token = bin2hex(random_bytes(32));
    setcookie('visitor_token', $token, [
        'expires' => time() + 365 * 24 * 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['visitor_token'] = $token;
}

// --- Constantes métier ---
define('AVIS_MIN_NOTE', 0);
define('AVIS_MAX_NOTE', 10);
define('CHAT_POLL_INTERVAL_MS', 3000);
define('ML_MIN_AVIS_POUR_ENTRAINEMENT', 100);

// --- Régions de Madagascar ---
const REGIONS_MADAGASCAR = [
    'Antananarivo', 'Toamasina', 'Antsiranana', 'Fianarantsoa',
    'Mahajanga', 'Toliara', 'Ambovombe', 'Ambatondrazaka',
    'Morondava', 'Nosy Be', 'Sainte-Marie', 'Autre'
];

// --- Services ---
const SERVICES = [
    'mobile' => 'Mobile',
    'internet' => 'Internet',
    'fibre' => 'Fibre optique',
    'mobile_money' => 'Mobile Money',
    'autre' => 'Autre',
];