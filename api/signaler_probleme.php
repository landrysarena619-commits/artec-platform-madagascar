<?php
/**
 * Endpoint AJAX : signalement d'un problème (réclamation).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    json_response(['success' => false, 'message' => 'Session expirée.'], 403);
}

$publicationId = (int) ($_POST['publication_id'] ?? 0);
$operateurId   = (int) ($_POST['operateur_id'] ?? 0);
$sujet         = trim($_POST['sujet'] ?? '');
$categorie     = trim($_POST['categorie'] ?? 'autre');
$description   = trim($_POST['description'] ?? '');

if ($publicationId <= 0 || $operateurId <= 0 || $sujet === '' || $description === '') {
    json_response(['success' => false, 'message' => 'Tous les champs requis doivent être remplis.'], 400);
}
if (!in_array($categorie, ['reseau', 'facturation', 'service_client', 'offre_non_respectee', 'autre'], true)) {
    $categorie = 'autre';
}

try {
    $stmt = db()->prepare("
        INSERT INTO reclamations (operateur_id, publication_id, visitor_token, sujet, description, categorie, statut)
        VALUES (?, ?, ?, ?, ?, ?, 'nouveau')
    ");
    $stmt->execute([
        $operateurId,
        $publicationId,
        $_COOKIE['visitor_token'] ?? '',
        mb_substr($sujet, 0, 200),
        $description,
        $categorie,
    ]);

    log_action('signaler_probleme', 'reclamations', (int) db()->lastInsertId(), "Sujet : $sujet");

    json_response(['success' => true, 'message' => 'Votre signalement a été transmis. Merci.']);
} catch (Throwable $e) {
    error_log('signaler_probleme error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Erreur technique.'], 500);
}