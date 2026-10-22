<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Réclamations', 'Suivez les demandes et apportez une réponse documentée.', 'reclamations.php', 'operator');
$code = current_user()['operator_code'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? null)) {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $response = trim((string) ($_POST['operator_response'] ?? ''));
    $status = in_array($_POST['status'] ?? '', ['in_progress', 'resolved'], true) ? $_POST['status'] : 'in_progress';
    if ($id && $response !== '') db()->prepare('UPDATE complaints SET operator_response=?, status=? WHERE id=? AND operator_code=?')->execute([$response, $status, $id, $code]);
    flash('success', 'Réclamation mise à jour.'); header('Location: ' . url('operator/reclamations.php')); exit;
}
$statement = db()->prepare('SELECT * FROM complaints WHERE operator_code=? ORDER BY created_at DESC'); $statement->execute([$code]);
?>
<div class="panel table-wrap"><table><thead><tr><th>Demande</th><th>Région</th><th>Statut</th><th>Réponse</th></tr></thead><tbody><?php foreach ($statement->fetchAll() as $item): ?><tr><td><strong><?= e($item['subject']) ?></strong><br><?= e($item['description']) ?><br><small><?= e(date('d/m/Y H:i', strtotime($item['created_at']))) ?></small></td><td><?= e($item['region']) ?></td><td><span class="badge <?= $item['status'] === 'open' ? 'warning' : ($item['status'] === 'resolved' ? 'success' : '') ?>"><?= e($item['status']) ?></span></td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><textarea name="operator_response" maxlength="2000" required placeholder="Réponse à la réclamation…"><?= e($item['operator_response'] ?? '') ?></textarea><select name="status"><option value="in_progress">En cours</option><option value="resolved" <?= $item['status'] === 'resolved' ? 'selected' : '' ?>>Résolue</option></select> <button class="button secondary" type="submit">Mettre à jour</button></form></td></tr><?php endforeach; ?></tbody></table></div>
<?php workspace_end(); ?>