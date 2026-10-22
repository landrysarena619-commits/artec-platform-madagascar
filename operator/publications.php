<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Mes publications', 'Gérez les offres visibles sur l’espace public.', 'publications.php', 'operator');
$code = current_user()['operator_code'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? null) && ($_POST['action'] ?? '') === 'delete') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    db()->prepare("UPDATE publications SET status = 'archived' WHERE id = ? AND operator_code = ?")->execute([$id, $code]);
    flash('success', 'La publication a été archivée.');
    header('Location: ' . url('operator/publications.php')); exit;
}
$query = db()->prepare('SELECT p.*, COUNT(r.id) AS review_count FROM publications p LEFT JOIN reviews r ON r.publication_id = p.id WHERE p.operator_code = ? GROUP BY p.id ORDER BY p.created_at DESC');
$query->execute([$code]); $publications = $query->fetchAll();
?>
<div class="toolbar"><span><?= count($publications) ?> publication(s)</span><a class="button" href="<?= e(url('operator/publication_edit.php')) ?>">＋ Nouvelle publication</a></div>
<div class="panel table-wrap"><table><thead><tr><th>Offre</th><th>Statut</th><th>Région</th><th>Avis</th><th>Publication</th><th>Actions</th></tr></thead><tbody><?php foreach ($publications as $item): ?><tr><td><strong><?= e($item['title']) ?></strong><br><small><?= e($item['summary']) ?></small></td><td><span class="badge <?= $item['status'] === 'published' ? 'success' : '' ?>"><?= e($item['status']) ?></span></td><td><?= e($item['region']) ?></td><td><?= (int) $item['review_count'] ?></td><td><?= e(date('d/m/Y', strtotime($item['created_at']))) ?></td><td><a class="button secondary" href="<?= e(url('operator/publication_edit.php?id=' . (int) $item['id'])) ?>">Modifier</a><?php if ($item['status'] !== 'archived'): ?><form method="post" style="display:inline" data-confirm="Archiver cette publication ?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="button danger" type="submit">Archiver</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php workspace_end(); ?>