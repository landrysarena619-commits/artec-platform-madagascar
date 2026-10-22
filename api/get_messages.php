<?php
/**
 * Endpoint AJAX : récupération des messages (polling toutes les 3s).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

$publicationId = (int) ($_GET['publication_id'] ?? 0);
$operateurId   = (int) ($_GET['operateur_id'] ?? 0);
$sinceId       = (int) ($_GET['since_id'] ?? 0);
$user = current_user();

if ($user && $user['role'] === 'operateur') {
    // ========== CÔTÉ OPÉRATEUR : messages d'un visiteur précis ==========
    $visitorToken = trim($_GET['visitor_token'] ?? '');
    if ($visitorToken === '') {
        json_response(['success' => false, 'message' => 'Token manquant.'], 400);
    }
    $convKey = 'pub_' . $publicationId . '_' . substr(hash('sha256', $visitorToken), 0, 24);

    $stmt = db()->prepare("
        SELECT id, sender_type, sender_id, sender_token, contenu, lu, created_at
        FROM messages
        WHERE conversation_key = ? AND id > ?
        ORDER BY id ASC
        LIMIT 100
    ");
    $stmt->execute([$convKey, $sinceId]);
    $messages = $stmt->fetchAll();

    // Marquer comme lus les messages du visiteur
    db()->prepare("
        UPDATE messages SET lu = 1
        WHERE conversation_key = ? AND sender_type = 'visiteur' AND lu = 0
    ")->execute([$convKey]);

    $myRole = 'operateur';
} else {
    // ========== CÔTÉ VISITEUR ==========
    $visitorToken = $_COOKIE['visitor_token'] ?? '';
    if ($publicationId <= 0 || $operateurId <= 0 || $visitorToken === '') {
        json_response(['success' => false, 'message' => 'Données manquantes.'], 400);
    }
    $convKey = 'pub_' . $publicationId . '_' . substr(hash('sha256', $visitorToken), 0, 24);

    $stmt = db()->prepare("
        SELECT id, sender_type, sender_id, sender_token, contenu, lu, created_at
        FROM messages
        WHERE conversation_key = ? AND id > ?
        ORDER BY id ASC
        LIMIT 100
    ");
    $stmt->execute([$convKey, $sinceId]);
    $messages = $stmt->fetchAll();

    // Marquer comme lus les messages de l'opérateur
    db()->prepare("
        UPDATE messages SET lu = 1
        WHERE conversation_key = ? AND sender_type = 'operateur' AND lu = 0
    ")->execute([$convKey]);

    $myRole = 'visiteur';
}

// --- Formatage ---
$out = [];
foreach ($messages as $m) {
    $mine = ($myRole === 'operateur' && $m['sender_type'] === 'operateur')
         || ($myRole === 'visiteur' && $m['sender_type'] === 'visiteur');
    $out[] = [
        'id' => (int) $m['id'],
        'contenu' => $m['contenu'],
        'mine' => $mine,
        'sender_type' => $m['sender_type'],
        'time' => date('H:i', strtotime($m['created_at'])),
    ];
}

json_response([
    'success' => true,
    'messages' => $out,
    'last_id' => empty($messages) ? $sinceId : (int) end($messages)['id'],
]);