<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Journaux d’activité', 'Suivez les connexions et actions de la plateforme.', 'logs.php', 'admin');
$rows = db()->query('SELECT l.*, u.name, u.email FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 250')->fetchAll();
?>
<div class="panel table-wrap"><table><thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Détails</th><th>Empreinte IP</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e(date('d/m/Y H:i:s', strtotime($row['created_at']))) ?></td><td><?= e($row['name'] ?: 'Visiteur') ?><br><small><?= e($row['email'] ?? '') ?></small></td><td><?= e($row['action']) ?></td><td><?= e($row['details'] ?? '') ?></td><td><small><?= e(substr((string) $row['ip_hash'], 0, 14)) ?></small></td></tr><?php endforeach; ?></tbody></table></div>
<?php workspace_end(); ?>