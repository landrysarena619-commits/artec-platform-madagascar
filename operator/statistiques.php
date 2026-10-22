<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Statistiques', 'Évolution de la satisfaction de vos clients.', 'statistiques.php', 'operator');
$code = current_user()['operator_code'];
$summary = db()->prepare("SELECT COUNT(*) AS total, ROUND(AVG(rating), 2) AS average, COUNT(DISTINCT region) AS regions FROM reviews WHERE operator_code=? AND status='published'"); $summary->execute([$code]); $stats = $summary->fetch();
$byRegion = db()->prepare("SELECT region, COUNT(*) AS total, ROUND(AVG(rating),1) AS average FROM reviews WHERE operator_code=? AND status='published' GROUP BY region ORDER BY total DESC"); $byRegion->execute([$code]);
?>
<div class="stats-strip"><div class="stat-box"><span>Avis publiés</span><strong><?= (int) $stats['total'] ?></strong></div><div class="stat-box"><span>Note moyenne</span><strong><?= $stats['average'] !== null ? e($stats['average']) . '<small style="font:500 13px DM Sans"> / 10</small>' : '—' ?></strong></div><div class="stat-box"><span>Régions représentées</span><strong><?= (int) $stats['regions'] ?></strong></div></div>
<section class="panel"><h2>Répartition par région</h2><div class="table-wrap"><table><thead><tr><th>Région</th><th>Avis</th><th>Note moyenne</th></tr></thead><tbody><?php foreach ($byRegion->fetchAll() as $row): ?><tr><td><?= e($row['region']) ?></td><td><?= (int) $row['total'] ?></td><td><?= e($row['average']) ?>/10</td></tr><?php endforeach; ?></tbody></table></div></section>
<?php workspace_end(); ?>