<?php
require_once __DIR__ . '/../includes/functions.php';
require_post_csrf();

if (!empty($_POST['website'])) json_response(['ok' => true, 'message' => 'Merci pour votre avis.']);
$publicationId = filter_var($_POST['publication_id'] ?? null, FILTER_VALIDATE_INT);
$rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
$comment = trim((string) ($_POST['comment'] ?? ''));
$region = trim((string) ($_POST['region'] ?? ''));
$service = trim((string) ($_POST['service'] ?? ''));
$visitorName = trim((string) ($_POST['visitor_name'] ?? ''));
if (!$publicationId || $rating === false || $rating < 0 || $rating > 10 || mb_strlen($comment) < 8 || mb_strlen($comment) > 2000 || $region === '' || $service === '') {
    json_response(['ok' => false, 'message' => 'Vérifiez les champs : note de 0 à 10, commentaire et informations de service requis.'], 422);
}

$pdo = db();
$publicationQuery = $pdo->prepare("SELECT id, operator_code FROM publications WHERE id = ? AND status = 'published' LIMIT 1");
$publicationQuery->execute([$publicationId]);
$publication = $publicationQuery->fetch();
if (!$publication) json_response(['ok' => false, 'message' => 'Cette publication n’est plus disponible.'], 404);

$ipHash = hash_ip();
$limit = (int) ($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'review_daily_limit'")->fetchColumn() ?: 3);
$rateCheck = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE ip_hash = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
$rateCheck->execute([$ipHash]);
if ((int) $rateCheck->fetchColumn() >= max(1, min(10, $limit))) {
    json_response(['ok' => false, 'message' => 'La limite quotidienne d’avis est atteinte. Réessayez demain.'], 429);
}

$insert = $pdo->prepare('INSERT INTO reviews (publication_id, operator_code, rating, comment, region, service, visitor_name, ip_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$insert->execute([$publicationId, $publication['operator_code'], $rating, $comment, mb_substr($region, 0, 100), mb_substr($service, 0, 100), $visitorName !== '' ? mb_substr($visitorName, 0, 100) : null, $ipHash]);
json_response(['ok' => true, 'message' => 'Votre avis a été publié. Merci de contribuer à l’observatoire.']);