<?php
define('APP_ENV', 'local');
define('APP_NAME', 'ARTEC Madagascar');
define('APP_VERSION', '1.0.0');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$projectRoot = '/artec-platform-madagascar';
define('BASE_URL', $protocol . '://' . $host . $projectRoot . '/');

define('ROOT_PATH', dirname(__DIR__));
define('ASSETS_URL', BASE_URL . 'assets/');
define('LOGS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'logs');

date_default_timezone_set('Indian/Antananarivo');

if (APP_ENV === 'local') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (APP_ENV === 'production'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('ARTECSESSID');
    session_start();
}

if (empty($_COOKIE['visitor_token'])) {
    $token = bin2hex(random_bytes(32));
    setcookie('visitor_token', $token, [
        'expires'  => time() + 365 * 24 * 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['visitor_token'] = $token;
}

define('AVIS_MIN_NOTE', 0);
define('AVIS_MAX_NOTE', 10);
define('CHAT_POLL_INTERVAL_MS', 3000);
define('ML_MIN_AVIS_POUR_ENTRAINEMENT', 100);

const REGIONS_MADAGASCAR = [
    'Antananarivo', 'Toamasina', 'Antsiranana', 'Fianarantsoa',
    'Mahajanga', 'Toliara', 'Ambovombe', 'Ambatondrazaka',
    'Morondava', 'Nosy Be', 'Sainte-Marie', 'Autre'
];

const SERVICES = [
    'mobile'       => 'Mobile',
    'internet'     => 'Internet',
    'fibre'        => 'Fibre optique',
    'mobile_money' => 'Mobile Money',
    'autre'        => 'Autre',
];