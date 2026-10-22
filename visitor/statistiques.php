<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Statistiques publiques';
$isPublic = true;
$rows = db()->query("SELECT operator_code, COUNT(*) AS review_count, ROUND(AVG(rating), 2) AS average_rating, COUNT(DISTINCT region) AS region_count FROM reviews WHERE status = 'published' GROUP BY operator_code")->fetchAll();
$map = [];
foreach ($rows as $row) $map[$row['operator_code']] = $row;
$operators = ['orange' => 'Orange', 'yas' => 'Yas', 'airtel' => 'Airtel'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-top"><div><div class="eyebrow">Données ouvertes</div><h1>La qualité, en perspective.</h1><p>Comparaison des avis publiés par les utilisateurs.</p></div></div>
<div class="stats-strip"><?php foreach ($operators as $code => $name): $stat = $map[$code] ?? ['review_count' => 0, 'average_rating' => null, 'region_count' => 0]; ?><div class="stat-box operator-<?= e($code) ?>"><span><?= e($name) ?> · note moyenne</span><strong><?= $stat['average_rating'] !== null ? e(number_format((float) $stat['average_rating'], 1)) . '<small style="font:500 13px DM Sans"> / 10</small>' : '—' ?></strong><span><?= (int) $stat['review_count'] ?> avis · <?= (int) $stat['region_count'] ?> régions</span></div><?php endforeach; ?><div class="stat-box"><span>Avis publiés au total</span><strong><?= array_sum(array_map(static fn($item) => (int) $item['review_count'], $map)) ?></strong><span>Mise à jour en direct</span></div></div>
<section class="panel"><div class="section-heading"><div><div class="eyebrow">Comparatif opérateurs</div><h2>Notes de satisfaction</h2></div></div><div class="chart-wrap"><canvas id="publicStatsChart" aria-label="Notes moyennes par opérateur"></canvas></div></section>
<section style="margin-top:25px" class="panel"><h2>Comment lire ces chiffres ?</h2><p style="color:#617371">Les moyennes sont calculées à partir des avis publiés. Elles reflètent les contributions reçues et ne constituent pas une mesure exhaustive de la qualité du réseau.</p></section>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script>window.publicStats = <?= json_encode(['labels' => array_values($operators), 'values' => array_map(static fn($code) => isset($map[$code]['average_rating']) ? (float) $map[$code]['average_rating'] : 0, array_keys($operators))], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script><script src="<?= e(url('assets/js/charts.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>