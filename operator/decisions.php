<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Décisions ARTEC', 'Consultez les avis réglementaires adressés à votre opérateur.', 'decisions.php', 'operator');
$statement = db()->prepare('SELECT d.*, u.name AS author_name FROM decisions d LEFT JOIN users u ON u.id=d.author_id WHERE d.operator_code=? OR d.operator_code IS NULL ORDER BY d.created_at DESC');
$statement->execute([current_user()['operator_code']]);
?>
<div class="review-list"><?php foreach ($statement->fetchAll() as $item): ?><article class="panel"><div class="review-top"><span><?= e(strtoupper($item['decision_type'])) ?> · <?= e(date('d/m/Y', strtotime($item['created_at']))) ?></span><span class="badge <?= $item['decision_type'] === 'warning' ? 'warning' : '' ?>"><?= e($item['status']) ?></span></div><h2 style="font-size:19px;margin:10px 0"><?= e($item['title']) ?></h2><p><?= nl2br(e($item['body'])) ?></p><?php if ($item['due_date']): ?><small>Échéance : <?= e(date('d/m/Y', strtotime($item['due_date']))) ?></small><?php endif; ?><div style="font-size:12px;color:#71817c">Émis par <?= e($item['author_name']) ?></div></article><?php endforeach; ?></div>
<?php workspace_end(); ?>