<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$stmt = db()->query("
    SELECT
        o.id, o.nom, o.slug, o.couleur,
        COUNT(a.id) AS nb_avis,
        COALESCE(ROUND(AVG(a.note), 2), 0) AS note_moy,
        SUM(CASE WHEN a.note >= 7 THEN 1 ELSE 0 END) AS satisfaits,
        SUM(CASE WHEN a.note <= 3 THEN 1 ELSE 0 END) AS insatisfaits
    FROM operateurs o
    LEFT JOIN avis a ON a.operateur_id = o.id AND a.publie = 1
    WHERE o.actif = 1
    GROUP BY o.id
    ORDER BY o.nom
");
$operateurs = $stmt->fetchAll();

$stmt = db()->query("
    SELECT
        o.slug,
        SUM(CASE WHEN a.note BETWEEN 0 AND 2 THEN 1 ELSE 0 END) AS n_0_2,
        SUM(CASE WHEN a.note BETWEEN 3 AND 5 THEN 1 ELSE 0 END) AS n_3_5,
        SUM(CASE WHEN a.note BETWEEN 6 AND 8 THEN 1 ELSE 0 END) AS n_6_8,
        SUM(CASE WHEN a.note BETWEEN 9 AND 10 THEN 1 ELSE 0 END) AS n_9_10
    FROM operateurs o
    LEFT JOIN avis a ON a.operateur_id = o.id AND a.publie = 1
    WHERE o.actif = 1
    GROUP BY o.id
");
$repartition = $stmt->fetchAll();
$rep = array_column($repartition, null, 'slug');

$stmt = db()->query("
    SELECT
        o.slug,
        DATE_FORMAT(a.created_at, '%Y-%m') AS mois,
        ROUND(AVG(a.note), 2) AS moy
    FROM operateurs o
    LEFT JOIN avis a ON a.operateur_id = o.id
        AND a.publie = 1
        AND a.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    WHERE o.actif = 1
    GROUP BY o.id, DATE_FORMAT(a.created_at, '%Y-%m')
    ORDER BY mois
");
$evolution = $stmt->fetchAll();

$moisSet = [];
$evoSéries = ['orange' => [], 'yas' => [], 'airtel' => []];
foreach ($evolution as $e) {
    if ($e['mois']) $moisSet[$e['mois']] = true;
}
$moisLabels = array_keys($moisSet);
sort($moisLabels);

foreach ($evoSéries as $slug => &$serie) {
    foreach ($moisLabels as $m) {
        $found = null;
        foreach ($evolution as $e) {
            if ($e['slug'] === $slug && $e['mois'] === $m) { $found = (float) $e['moy']; break; }
        }
        $serie[] = $found;
    }
}

$stmt = db()->query("
    SELECT region, COUNT(*) AS nb, ROUND(AVG(note), 1) AS moy
    FROM avis WHERE publie = 1
    GROUP BY region
    ORDER BY nb DESC
    LIMIT 8
");
$regions = $stmt->fetchAll();

$totaux = db()->query("
    SELECT
        COUNT(*) AS nb_avis_total,
        ROUND(AVG(note), 1) AS note_globale
    FROM avis WHERE publie = 1
")->fetch();

$pageTitle = 'Statistiques publiques';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero-artec mb-4" style="padding: 3rem 0;">
    <div class="container">
        <h1 class="fw-bold mb-2"><i class="bi bi-bar-chart-line"></i> Statistiques publiques</h1>
        <p class="lead mb-0">Comparez la qualite des services des 3 operateurs, basee sur les avis des citoyens.</p>
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi-card text-center">
            <div class="kpi-value"><?= (int) $totaux['nb_avis_total'] ?></div>
            <div class="kpi-label justify-content-center">Avis citoyens collectes</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card kpi-orange text-center">
            <div class="kpi-value"><?= number_format((float) $totaux['note_globale'], 1) ?><span class="fs-5">/10</span></div>
            <div class="kpi-label justify-content-center">Note moyenne globale</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card kpi-yas text-center">
            <div class="kpi-value"><?= count($operateurs) ?></div>
            <div class="kpi-label justify-content-center">Operateurs compares</div>
        </div>
    </div>
</div>

<div class="card card-artec mb-4">
    <div class="card-header"><i class="bi bi-bar-chart"></i> Comparaison des notes moyennes</div>
    <div class="card-body">
        <canvas id="chartCompare" height="90"></canvas>
    </div>
</div>

<div class="row g-4 mb-4">
    <?php foreach ($operateurs as $op):
        $r = $rep[$op['slug']] ?? null;
        $total = max(1, array_sum([
            (int) ($r['n_0_2'] ?? 0),
            (int) ($r['n_3_5'] ?? 0),
            (int) ($r['n_6_8'] ?? 0),
            (int) ($r['n_9_10'] ?? 0),
        ]));
        $pct_satisfait = round(((int) ($r['n_6_8'] ?? 0) + (int) ($r['n_9_10'] ?? 0)) / $total * 100);
    ?>
        <div class="col-md-4">
            <div class="card card-artec h-100" style="border-top: 4px solid <?= e($op['couleur']) ?>;">
                <div class="card-body">
                    <h5 class="fw-bold mb-1"><?= e($op['nom']) ?></h5>
                    <div class="display-6 fw-bold text-artec mb-1">
                        <?= number_format((float) $op['note_moy'], 1) ?><span class="fs-5">/10</span>
                    </div>
                    <div class="rating-stars mb-2">
                        <?php
                        $s = round((float) $op['note_moy'] / 2);
                        for ($i = 1; $i <= 5; $i++) echo $i <= $s ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                        ?>
                    </div>
                    <div class="progress mb-2" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: <?= $pct_satisfait ?>%"></div>
                    </div>
                    <div class="small text-muted">
                        <strong><?= $pct_satisfait ?>%</strong> de citoyens satisfaits (superieur ou egal a 6/10)
                    </div>
                    <div class="mt-2 small">
                        <span class="text-success"><i class="bi bi-hand-thumbs-up"></i> <?= (int) $op['satisfaits'] ?> satisfaits</span>
                        <br>
                        <span class="text-danger"><i class="bi bi-hand-thumbs-down"></i> <?= (int) $op['insatisfaits'] ?> insatisfaits</span>
                    </div>
                    <div class="mt-2 small text-muted"><?= (int) $op['nb_avis'] ?> avis au total</div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card card-artec mb-4">
    <div class="card-header"><i class="bi bi-graph-up"></i> Evolution des notes (6 derniers mois)</div>
    <div class="card-body">
        <?php if (empty($moisLabels)): ?>
            <p class="text-muted mb-0">Pas encore assez de donnees pour afficher l'evolution.</p>
        <?php else: ?>
            <canvas id="chartEvolution" height="90"></canvas>
        <?php endif; ?>
    </div>
</div>

<div class="card card-artec mb-4">
    <div class="card-header"><i class="bi bi-pie-chart"></i> Repartition des notes par operateur</div>
    <div class="card-body">
        <canvas id="chartRepartition" height="90"></canvas>
    </div>
</div>

<div class="card card-artec">
    <div class="card-header"><i class="bi bi-geo-alt"></i> Top regions par nombre d'avis</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-artec mb-0">
                <thead><tr><th>Region</th><th>Nombre d'avis</th><th>Note moyenne</th></tr></thead>
                <tbody>
                    <?php foreach ($regions as $r): ?>
                        <tr>
                            <td><strong><?= e($r['region']) ?></strong></td>
                            <td><?= (int) $r['nb'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 12px; max-width: 200px;">
                                        <div class="progress-bar bg-<?= $r['moy'] >= 7 ? 'success' : ($r['moy'] >= 5 ? 'warning' : 'danger') ?>"
                                             style="width: <?= (float) $r['moy'] * 10 ?>%"></div>
                                    </div>
                                    <strong><?= number_format((float) $r['moy'], 1) ?>/10</strong>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('chartCompare'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($operateurs, 'nom')) ?>,
        datasets: [{
            label: 'Note moyenne /10',
            data: <?= json_encode(array_map(fn($o) => (float) $o['note_moy'], $operateurs)) ?>,
            backgroundColor: <?= json_encode(array_column($operateurs, 'couleur')) ?>,
            borderRadius: 8,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { min: 0, max: 10, ticks: { stepSize: 2 } } }
    }
});

<?php if (!empty($moisLabels)): ?>
new Chart(document.getElementById('chartEvolution'), {
    type: 'line',
    data: {
        labels: <?= json_encode($moisLabels) ?>,
        datasets: [
            {
                label: 'Orange',
                data: <?= json_encode($evoSéries['orange']) ?>,
                borderColor: '#FF7900',
                backgroundColor: 'rgba(255, 121, 0, .1)',
                borderWidth: 3,
                tension: .3,
                spanGaps: true
            },
            {
                label: 'Yas',
                data: <?= json_encode($evoSéries['yas']) ?>,
                borderColor: '#00A651',
                backgroundColor: 'rgba(0, 166, 81, .1)',
                borderWidth: 3,
                tension: .3,
                spanGaps: true
            },
            {
                label: 'Airtel',
                data: <?= json_encode($evoSéries['airtel']) ?>,
                borderColor: '#E40000',
                backgroundColor: 'rgba(228, 0, 0, .1)',
                borderWidth: 3,
                tension: .3,
                spanGaps: true
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { min: 0, max: 10 } }
    }
});
<?php endif; ?>

new Chart(document.getElementById('chartRepartition'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($operateurs, 'nom')) ?>,
        datasets: [
            {
                label: '0-2 (Mauvais)',
                data: <?= json_encode(array_map(fn($o) => (int) ($rep[$o['slug']]['n_0_2'] ?? 0), $operateurs)) ?>,
                backgroundColor: '#dc3545',
            },
            {
                label: '3-5 (Moyen)',
                data: <?= json_encode(array_map(fn($o) => (int) ($rep[$o['slug']]['n_3_5'] ?? 0), $operateurs)) ?>,
                backgroundColor: '#ffc107',
            },
            {
                label: '6-8 (Bien)',
                data: <?= json_encode(array_map(fn($o) => (int) ($rep[$o['slug']]['n_6_8'] ?? 0), $operateurs)) ?>,
                backgroundColor: '#0dcaf0',
            },
            {
                label: '9-10 (Excellent)',
                data: <?= json_encode(array_map(fn($o) => (int) ($rep[$o['slug']]['n_9_10'] ?? 0), $operateurs)) ?>,
                backgroundColor: '#198754',
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>