<?php
/**
 * Suppression d'une publication (POST sécurisé).
 */
require_once __DIR__ . '/../includes/auth.php';
require_role('operateur');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? '')) {
    flash('error', 'Requête invalide.', 'danger');
    redirect(BASE_URL . 'operator/publications.php');
}

$user = current_user();
$id = (int) ($_POST['id'] ?? 0);

$stmt = db()->prepare("DELETE FROM publications WHERE id = ? AND operateur_id = ?");
$stmt->execute([$id, $user['operateur_id']]);

if ($stmt->rowCount() > 0) {
    log_action('delete_publication', 'publications', $id, 'Suppression');
    flash('success', 'Publication supprimée.', 'success');
} else {
    flash('error', 'Publication introuvable ou non autorisée.', 'danger');
}

redirect(BASE_URL . 'operator/publications.php');