<?php
require_once __DIR__ . '/../includes/auth.php';
$user = current_user();
$type = (string) ($_GET['conversation_type'] ?? 'publication');
$after = max(0, (int) ($_GET['after'] ?? 0));
$key = (string) ($_GET['conversation_key'] ?? '');
if (!in_array($type, ['publication', 'regulatory'], true)) json_response(['ok' => false, 'message' => 'Conversation invalide.'], 422);

$params = [$key, $after];
$where = 'conversation_key = ? AND id > ?';
if (!$user) {
    if ($type !== 'publication' || empty($_SESSION['visitor_chat_token'])) json_response(['ok' => false, 'message' => 'Conversation inconnue.'], 403);
    $publicationId = (int) ($_GET['publication_id'] ?? 0);
    $expectedKey = 'pub:' . $publicationId . ':' . hash('sha256', $_SESSION['visitor_chat_token']);
    if (!hash_equals($expectedKey, $key)) json_response(['ok' => false, 'message' => 'Conversation inconnue.'], 403);
    $where .= ' AND publication_id = ?'; $params[] = $publicationId;
} elseif ($user['role'] === 'operator') {
    if ($type === 'regulatory' && $key !== 'reg:' . $user['operator_code']) json_response(['ok' => false, 'message' => 'Accès non autorisé.'], 403);
    $where .= ' AND operator_code = ? AND conversation_type = ?'; $params[] = $user['operator_code']; $params[] = $type;
} elseif ($user['role'] === 'artec') {
    if ($type !== 'regulatory' || !preg_match('/^reg:(orange|yas|airtel)$/', $key)) json_response(['ok' => false, 'message' => 'Accès non autorisé.'], 403);
    $where .= ' AND conversation_type = ?'; $params[] = $type;
} else json_response(['ok' => false, 'message' => 'Accès non autorisé.'], 403);

$statement = db()->prepare("SELECT id, sender_name, sender_role, body, created_at FROM messages WHERE $where ORDER BY id ASC LIMIT 100");
$statement->execute($params);
json_response(['ok' => true, 'messages' => $statement->fetchAll()]);