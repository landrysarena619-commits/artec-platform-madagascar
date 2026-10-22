<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Avis reçus', 'Consultez et traitez les retours liés à vos offres.', 'avis.php', 'operator');
$code = current_user()['operator_code'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? null)) {
    $id = filter_var($_POST['review_id'] ?? null, FILTER_VALIDATE_INT);
    $reply = trim((string) ($_POST['operator_reply'] ?? ''));
    if ($id && mb_strlen($reply) >= 2 && mb_strlen($reply) <= 2000) db()->prepare('UPDATE reviews SET operator_reply = ? WHERE id = ? AND operator_code = ?')->execute([$reply, $id, $code]);
    flash('success', 'Réponse enregistrée.'); header('Location: ' . url('operator/avis.php')); exit;
}
$status = in_array($_GET['status'] ?? '', ['published', 'flagged', 'hidden'], true) ? $_GET['status'] : 'published';
$statement = db()->prepare('SELECT r.*, p.title AS publication_title FROM reviews r JOIN publications p ON p.id=r.publication_id WHERE r.operator_code=? AND r.status=? ORDER BY r.created_at DESC LIMIT 150'); $statement->execute([$code, $status]);
?>
<div class="toolbar"><form method="get"><select name="status"><option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Publiés</option><option value="flagged" <?= $status === 'flagged' ? 'selected' : '' ?>>Signalés</option><option value="hidden" <?= $status === 'hidden' ? 'selected' : '' ?>>Masqués</option></select><button class="button secondary">Filtrer</button></form><a class="button" href="<?= e(url('operator/export_excel.php')) ?>">Exporter CSV</a></div>
<div class="panel table-wrap"><table><thead><tr><th>Avis</th><th>Note</th><th>Service / région</th><th>Réponse</th></tr></thead><tbody><?php foreach ($statement->fetchAll() as $review): ?><tr><td><strong><?= e($review['publication_title']) ?></strong><br><?= e($review['visitor_name'] ?: 'Visiteur') ?> · <?= e(date('d/m/Y', strtotime($review['created_at']))) ?><br><?= e($review['comment']) ?></td><td><span class="rating-pill"><?= (int) $review['rating'] ?>/10</span></td><td><?= e($review['service']) ?><br><?= e($review['region']) ?></td><td><?php if ($status === 'published'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>"><textarea name="operator_reply" maxlength="2000" placeholder="Répondre à cet avis…" required><?= e($review['operator_reply'] ?? '') ?></textarea><button class="button secondary" type="submit">Enregistrer</button></form><?php else: ?><span class="badge warning">À traiter par l’ARTEC</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php workspace_end(); ?>