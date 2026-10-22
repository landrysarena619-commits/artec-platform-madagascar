<?php
require_once __DIR__ . '/../includes/auth.php';
require_post_csrf();
$user = current_user();
if (!$user || !in_array($user['role'], ['operator', 'artec'], true)) json_response(['ok' => false], 403);
$messageId = filter_var($_POST['message_id'] ?? null, FILTER_VALIDATE_INT);
$query = db()->prepare('SELECT operator_code, conversation_type FROM messages WHERE id = ?');
$query->execute([$messageId]);
$message = $query->fetch();
if (!$message || ($user['role'] === 'operator' && $message['operator_code'] !== $user['operator_code']) || ($user['role'] === 'artec' && $message['conversation_type'] !== 'regulatory')) json_response(['ok' => false], 403);
db()->prepare('INSERT IGNORE INTO message_reads (message_id, user_id) VALUES (?, ?)')->execute([$messageId, $user['id']]);
json_response(['ok' => true]);