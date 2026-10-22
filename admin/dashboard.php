<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Administration', 'Gestion de la plateforme et de ses accès.', 'dashboard.php', 'admin');
$pdo = db();
$users = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$active = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_active=1')->fetchColumn();
$logs = (int) $pdo->query('SELECT COUNT(*) FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)')->fetchColumn();
$reviews = (int) $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
?>
<div class="dashboard-grid"><div class="dash-stat"><small>Comptes</small><strong><?= $users ?></strong><em><?= $active ?> actifs</em></div><div class="dash-stat"><small>Avis enregistrés</small><strong><?= $reviews ?></strong><em>Tous statuts</em></div><div class="dash-stat"><small>Actions récentes</small><strong><?= $logs ?></strong><em>Dernières 24 heures</em></div></div><div class="panel" style="margin-top:18px"><h2>État de la plateforme</h2><p>La base de données est joignable. Les journaux et les sauvegardes de la base doivent être conservés selon la politique d’exploitation de votre organisation.</p><a class="button secondary" href="<?= e(url('admin/logs.php')) ?>">Consulter les journaux</a></div>
<?php workspace_end(); ?>