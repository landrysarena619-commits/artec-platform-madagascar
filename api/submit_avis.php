<?php
/**
 * Endpoint AJAX : soumission d'un avis visiteur.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
}

// --- Vérif CSRF ---
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    json_response(['success' => false, 'message' => 'Session expirée. Rechargez la page.'], 403);
}

// --- Récupération ---
$publicationId = (int) ($_POST['publication_id'] ?? 0);
$operateurId   = (int) ($_POST['operateur_id'] ?? 0);
$note          = (int) ($_POST['note'] ?? -1);
$region        = trim($_POST['region'] ?? '');
$service       = trim($_POST['service'] ?? '');
$commentaire   = trim($_POST['commentaire'] ?? '');

// --- Validations ---
if ($publicationId <= 0 || $operateurId <= 0) {
    json_response(['success' => false, 'message' => 'Publication invalide.'], 400);
}
if ($note < AVIS_MIN_NOTE || $note > AVIS_MAX_NOTE) {
    json_response(['success' => false, 'message' => 'Note invalide (0 à 10).'], 400);
}
if (!in_array($region, REGIONS_MADAGASCAR, true)) {
    json_response(['success' => false, 'message' => 'Région invalide.'], 400);
}
if (!array_key_exists($service, SERVICES)) {
    json_response(['success' => false, 'message' => 'Service invalide.'], 400);
}
if (mb_strlen($commentaire) > 1000) {
    json_response(['success' => false, 'message' => 'Commentaire trop long (max 1000 caractères).'], 400);
}

// --- Vérifier que la publication existe ---
$stmt = db()->prepare("SELECT id FROM publications WHERE id = ? AND operateur_id = ?");
$stmt->execute([$publicationId, $operateurId]);
if (!$stmt->fetch()) {
    json_response(['success' => false, 'message' => 'Publication introuvable.'], 404);
}

// --- Anti-spam : 1 avis par IP par opérateur par 24h ---
$ipHash = hashIp();
$stmt = db()->prepare("
    SELECT COUNT(*) FROM avis
    WHERE ip_hash = ? AND operateur_id = ? AND created_at > (NOW() - INTERVAL 24 HOUR)
");
$stmt->execute([$ipHash, $operateurId]);
if ((int) $stmt->fetchColumn() > 0) {
    json_response([
        'success' => false,
        'message' => 'Vous avez déjà laissé un avis sur cet opérateur dans les dernières 24h. Merci de votre compréhension.'
    ], 429);
}

// --- Insertion ---
try {
    $stmt = db()->prepare("
        INSERT INTO avis (publication_id, operateur_id, visitor_token, ip_hash, note, commentaire, region, service, publie)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([
        $publicationId,
        $operateurId,
        $_COOKIE['visitor_token'] ?? '',
        $ipHash,
        $note,
        $commentaire !== '' ? $commentaire : null,
        $region,
        $service,
    ]);

    log_action('submit_avis', 'avis', (int) db()->lastInsertId(), "Note $note pour opérateur $operateurId");

    json_response(['success' => true, 'message' => 'Merci ! Votre avis a bien été enregistré.']);
} catch (Throwable $e) {
    error_log('submit_avis error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Erreur technique. Réessayez.'], 500);
}