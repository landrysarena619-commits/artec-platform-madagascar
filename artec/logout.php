<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) log_action('logout', 'users', current_user()['id'] ?? null, 'Déconnexion ARTEC');
$_SESSION = [];
session_destroy();
redirect(BASE_URL . 'visitor/index.php');