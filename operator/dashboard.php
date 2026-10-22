<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Tableau de bord', 'Votre activité et vos indicateurs clés.', 'dashboard.php', 'operator');
$code = current_user()['operator_code'];
$pdo = db();
$publicationCount = $pdo->prepare("SELECT COUNT(*) FROM publications WHERE operator_code = ? AND status = 'published'"); $publicationCount->execute([$code]);
$reviewCount = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE operator_code = ? AND status = 'published'"); $reviewCount->execute([$code]);
$average = $pdo->prepare("SELECT ROUND(AVG(rating), 1) FROM reviews WHERE operator_code = ? AND status = 'published'"); $average->execute([$code]); $averageScore = $average->fetchColumn();
$complaints = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE operator_code = ? AND status IN ('open','in_progress')"); $complaints->execute([$code]);
$recent = $pdo->prepare("SELECT r.*, p.title AS publication_title FROM reviews r JOIN publications p ON p.id = r.publication_id WHERE r.operator_code = ? AND r.status = 'published' ORDER BY r.created_at DESC LIMIT 5"); $recent->execute([$code]);
?>
<div class="dashboard-grid"><div class="dash-stat"><small>Offres publiées</small><strong><?= (int) $publicationCount->fetchColumn() ?></strong><em>Publications actives</em></div><div class="dash-stat"><small>Avis reçus</small><strong><?= (int) $reviewCount->fetchColumn() ?></strong><em>Depuis l’ouverture</em></div><div class="dash-stat"><small>Satisfaction moyenne</small><strong><?= $averageScore !== false && $averageScore !== null ? e($averageScore) : '—' ?></strong><em>Sur 10</em></div><div class="dash-stat"><small>Réclamations ouvertes</small><strong><?= (int) $complaints->fetchColumn() ?></strong><em>À traiter</em></div></div>
<div class="toolbar"><h2 style="font-size:19px;margin:0">Avis récents</h2><a class="button secondary" href="<?= e(url('operator/avis.php')) ?>">Tous les avis →</a></div>
<div class="panel table-wrap"><table><thead><tr><th>Publication</th><th>Note</th><th>Région</th><th>Commentaire</th><th>Date</th></tr></thead><tbody><?php foreach ($recent->fetchAll() as $review): ?><tr><td><?= e($review['publication_title']) ?></td><td><span class="rating-pill"><?= (int) $review['rating'] ?>/10</span></td><td><?= e($review['region']) ?></td><td><?= e(mb_strimwidth($review['comment'], 0, 90, '…')) ?></td><td><?= e(date('d/m/Y', strtotime($review['created_at']))) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php workspace_end(); ?>