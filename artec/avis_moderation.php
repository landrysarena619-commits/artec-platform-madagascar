<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Modération des avis', 'Examinez les contributions signalées par la communauté.', 'avis_moderation.php', 'artec');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? null)) {
    $id = filter_var($_POST['review_id'] ?? null, FILTER_VALIDATE_INT);
    $status = in_array($_POST['status'] ?? '', ['published', 'hidden'], true) ? $_POST['status'] : 'hidden';
    if ($id) { db()->prepare("UPDATE reviews SET status=? WHERE id=? AND status='flagged'")->execute([$status, $id]); log_activity('review_moderated', current_user()['id'], ['review_id' => $id, 'status' => $status]); }
    flash('success', 'Décision de modération enregistrée.'); header('Location: ' . url('artec/avis_moderation.php')); exit;
}
$rows = db()->query("SELECT r.*, p.title AS publication_title FROM reviews r JOIN publications p ON p.id=r.publication_id WHERE r.status='flagged' ORDER BY r.created_at ASC")->fetchAll();
?>
<div class="panel table-wrap"><table><thead><tr><th>Avis signalé</th><th>Opérateur</th><th>Note</th><th>Décision</th></tr></thead><tbody><?php foreach ($rows as $item): ?><tr><td><strong><?= e($item['publication_title']) ?></strong><br><?= e($item['comment']) ?><br><small><?= e($item['region']) ?> · <?= e(date('d/m/Y H:i', strtotime($item['created_at']))) ?></small></td><td><?= e(ucfirst($item['operator_code'])) ?></td><td><?= (int) $item['rating'] ?>/10</td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="review_id" value="<?= (int) $item['id'] ?>"><button class="button" name="status" value="published">Publier</button> <button class="button danger" name="status" value="hidden">Masquer</button></form></td></tr><?php endforeach; ?></tbody></table><?php if (!$rows): ?><p class="empty-state">Aucun avis ne nécessite de modération.</p><?php endif; ?></div>
<?php workspace_end(); ?>