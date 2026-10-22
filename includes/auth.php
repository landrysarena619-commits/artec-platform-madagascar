<?php
/**
 * Vérification d'authentification et de rôle.
 * À inclure en haut de chaque page protégée.
 *
 * Usage :
 *   require_once __DIR__ . '/../includes/auth.php';
 *   require_role('artec'); // bloque si pas connecté ou mauvais rôle
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function require_login(): void {
    if (!is_logged_in()) {
        flash('error', 'Veuillez vous connecter pour accéder à cette page.', 'warning');
        redirect(BASE_URL . 'visitor/index.php');
    }
}

function require_role(string ...$roles): void {
    require_login();
    if (!has_role(...$roles)) {
        http_response_code(403);
        flash('error', 'Accès refusé.', 'danger');
        redirect(BASE_URL . 'visitor/index.php');
    }
}