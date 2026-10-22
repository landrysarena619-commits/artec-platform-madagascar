<?php
require_once __DIR__ . '/../includes/functions.php';
require_post_csrf();
$publicationId = filter_var($_POST['publication_id'] ?? null, FILTER_VALIDATE_INT);
$subject = trim((string) ($_POST['subject'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$region = trim((string) ($_POST['region'] ?? ''));
if (!$publicationId || mb_strlen($subject) < 4 || mb_strlen($subject) > 180 || mb_strlen($description) < 15 || mb_strlen($description) > 2000 || $region === '') {
    json_response(['ok' => false, 'message' => 'Vérifiez le sujet, la région et la description du problème.'], 422);
}
$query = db()->prepare("SELECT id, operator_code FROM publications WHERE id=? AND status='published'");
$query->execute([$publicationId]);
$publication = $query->fetch();
if (!$publication) json_response(['ok' => false, 'message' => 'Publication introuvable.'], 404);
$ipHash = hash_ip();
$rate = db()->prepare('SELECT COUNT(*) FROM complaints WHERE ip_hash=? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
$rate->execute([$ipHash]);
if ((int) $rate->fetchColumn() >= 3) json_response(['ok' => false, 'message' => 'La limite quotidienne de signalements est atteinte.'], 429);
db()->prepare('INSERT INTO complaints (operator_code, subject, description, region, ip_hash) VALUES (?,?,?,?,?)')->execute([$publication['operator_code'], $subject, $description, mb_substr($region, 0, 100), $ipHash]);
json_response(['ok' => true, 'message' => 'Votre signalement a été transmis à l’opérateur.']);