<?php
/**
 * Endpoint AJAX : envoi d'un message.
 * Gère les conversations visiteur ↔ opérateur.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

// --- CSRF ---
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    json_response(['success' => false, 'message' => 'Session expirée. Rechargez la page.'], 403);
}

$contenu = trim($_POST['contenu'] ?? '');
if ($contenu === '') {
    json_response(['success' => false, 'message' => 'Message vide.'], 400);
}
if (mb_strlen($contenu) > 2000) {
    json_response(['success' => false, 'message' => 'Message trop long (max 2000 caractères).'], 400);
}

$user = current_user();

if ($user && $user['role'] === 'operateur') {
    // ========== L'OPÉRATEUR RÉPOND À UN VISITEUR ==========
    $visitorToken = trim($_POST['visitor_token'] ?? '');
    $publicationId = (int) ($_POST['publication_id'] ?? 0);
    $operateurId   = $user['operateur_id'];

    if ($visitorToken === '') {
        json_response(['success' => false, 'message' => 'Token visiteur manquant.'], 400);
    }

    $convKey = 'pub_' . $publicationId . '_' . substr(hash('sha256', $visitorToken), 0, 24);

    $stmt = db()->prepare("
        INSERT INTO messages
        (conversation_type, conversation_key, sender_type, sender_id, receiver_type,
         operateur_id, publication_id, contenu, lu)
        VALUES ('visiteur_operateur', ?, 'operateur', ?, 'visiteur', ?, ?, ?, 0)
    ");
    $stmt->execute([$convKey, $user['id'], $operateurId, $publicationId, $contenu]);

    json_response(['success' => true, 'message' => 'Message envoyé.']);

} else {
    // ========== LE VISITEUR ÉCRIT À L'OPÉRATEUR ==========
    $publicationId = (int) ($_POST['publication_id'] ?? 0);
    $operateurId   = (int) ($_POST['operateur_id'] ?? 0);
    $visitorToken  = $_COOKIE['visitor_token'] ?? '';

    if ($publicationId <= 0 || $operateurId <= 0 || $visitorToken === '') {
        json_response(['success' => false, 'message' => 'Données manquantes.'], 400);
    }

    // Vérifier que la publication existe
    $check = db()->prepare("SELECT id FROM publications WHERE id = ? AND operateur_id = ?");
    $check->execute([$publicationId, $operateurId]);
    if (!$check->fetch()) {
        json_response(['success' => false, 'message' => 'Publication introuvable.'], 404);
    }

    // Anti-spam : max 10 messages / minute par token visiteur
    $stmt = db()->prepare("
        SELECT COUNT(*) FROM messages
        WHERE sender_token = ? AND created_at > (NOW() - INTERVAL 1 MINUTE)
    ");
    $stmt->execute([$visitorToken]);
    if ((int) $stmt->fetchColumn() >= 10) {
        json_response(['success' => false, 'message' => 'Trop de messages envoyés. Patientez une minute.'], 429);
    }

    $convKey = 'pub_' . $publicationId . '_' . substr(hash('sha256', $visitorToken), 0, 24);

    $stmt = db()->prepare("
        INSERT INTO messages
        (conversation_type, conversation_key, sender_type, sender_token, receiver_type,
         operateur_id, publication_id, contenu, lu)
        VALUES ('visiteur_operateur', ?, 'visiteur', ?, 'operateur', ?, ?, ?, 0)
    ");
    $stmt->execute([$convKey, $visitorToken, $operateurId, $publicationId, $contenu]);

    json_response([
        'success' => true,
        'message' => 'Message envoyé.',
        'conversation_key' => $convKey,
    ]);
}