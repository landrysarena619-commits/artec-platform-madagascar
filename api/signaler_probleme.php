<?php
require_once __DIR__ . '/../includes/functions.php';
require_post_csrf();
$reviewId = filter_var($_POST['review_id'] ?? null, FILTER_VALIDATE_INT);
$query = db()->prepare("SELECT r.operator_code FROM reviews r WHERE r.id = ? AND r.status = 'published'");
$query->execute([$reviewId]);
$review = $query->fetch();
if (!$review) json_response(['ok' => false, 'message' => 'Avis introuvable.'], 404);
db()->prepare("UPDATE reviews SET status = 'flagged' WHERE id = ?")->execute([$reviewId]);
log_activity('review_flagged', null, ['review_id' => $reviewId, 'operator_code' => $review['operator_code']]);
json_response(['ok' => true, 'message' => 'Signalement transmis à la modération.']);