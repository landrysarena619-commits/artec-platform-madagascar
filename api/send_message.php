<?php
require_once __DIR__ . '/../includes/auth.php';
require_post_csrf();
$user = current_user();
$type = (string) ($_POST['conversation_type'] ?? 'publication');
$body = trim((string) ($_POST['body'] ?? ''));
if (!in_array($type, ['publication', 'regulatory'], true) || mb_strlen($body) < 1 || mb_strlen($body) > 2000) {
    json_response(['ok' => false, 'message' => 'Message invalide.'], 422);
}

if (!$user) {
    if ($type !== 'publication') json_response(['ok' => false, 'message' => 'Connexion requise.'], 401);
    $publicationId = filter_var($_POST['publication_id'] ?? null, FILTER_VALIDATE_INT);
    $publicationQuery = db()->prepare("SELECT id, operator_code FROM publications WHERE id = ? AND status = 'published' LIMIT 1");
    $publicationQuery->execute([$publicationId]);
    $publication = $publicationQuery->fetch();
    if (!$publication) json_response(['ok' => false, 'message' => 'Publication introuvable.'], 404);
    if (empty($_SESSION['visitor_chat_token'])) $_SESSION['visitor_chat_token'] = bin2hex(random_bytes(24));
    $token = $_SESSION['visitor_chat_token'];
    $conversationKey = 'pub:' . $publication['id'] . ':' . hash('sha256', $token);
    $rateCheck = db()->prepare('SELECT COUNT(*) FROM messages WHERE conversation_key = ? AND sender_role = \'visitor\' AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)');
    $rateCheck->execute([$conversationKey]);
    if ((int) $rateCheck->fetchColumn() >= 12) json_response(['ok' => false, 'message' => 'Trop de messages envoyés. Réessayez dans quelques minutes.'], 429);
    $operatorCode = $publication['operator_code'];
    $senderId = null;
    $senderName = trim((string) ($_POST['sender_name'] ?? 'Visiteur')) ?: 'Visiteur';
    $senderRole = 'visitor';
    $visitorHash = hash('sha256', $token);
} else {
    if (!in_array($user['role'], ['operator', 'artec'], true)) json_response(['ok' => false, 'message' => 'Accès non autorisé.'], 403);
    if ($type === 'regulatory' && !in_array($user['role'], ['operator', 'artec'], true)) json_response(['ok' => false, 'message' => 'Accès non autorisé.'], 403);
    if ($type === 'publication' && $user['role'] !== 'operator') json_response(['ok' => false, 'message' => 'Les échanges visiteurs sont réservés à l’opérateur.'], 403);
    $operatorCode = $user['role'] === 'operator' ? $user['operator_code'] : (string) ($_POST['operator_code'] ?? '');
    if (!in_array($operatorCode, ['orange', 'yas', 'airtel'], true)) json_response(['ok' => false, 'message' => 'Opérateur invalide.'], 422);
    $conversationKey = (string) ($_POST['conversation_key'] ?? '');
    if ($type === 'regulatory') {
        if ($conversationKey !== 'reg:' . $operatorCode) json_response(['ok' => false, 'message' => 'Conversation réglementaire invalide.'], 403);
    } else {
        $publicationId = filter_var($_POST['publication_id'] ?? null, FILTER_VALIDATE_INT);
        $publicationQuery = db()->prepare("SELECT id, operator_code FROM publications WHERE id = ? AND status = 'published'");
        $publicationQuery->execute([$publicationId]);
        $publication = $publicationQuery->fetch();
        if (!$publication || $publication['operator_code'] !== $operatorCode || !str_starts_with($conversationKey, 'pub:' . $publicationId . ':')) json_response(['ok' => false, 'message' => 'Conversation invalide.'], 403);
    }
    $senderId = (int) $user['id'];
    $senderName = $user['name'];
    $senderRole = $user['role'];
    $visitorHash = null;
}

$insert = db()->prepare('INSERT INTO messages (conversation_key, conversation_type, publication_id, operator_code, sender_user_id, sender_name, sender_role, body, visitor_token_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$insert->execute([$conversationKey, $type, $type === 'publication' ? (int) ($_POST['publication_id'] ?? 0) : null, $operatorCode, $senderId, mb_substr($senderName, 0, 100), $senderRole, $body, $visitorHash]);
json_response(['ok' => true, 'id' => (int) db()->lastInsertId()]);