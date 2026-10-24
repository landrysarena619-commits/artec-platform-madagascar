<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('artec');

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = db()->query("
        SELECT
            o.nom AS operateur,
            COUNT(a.id) AS nb_avis,
            COALESCE(ROUND(AVG(a.note),2),0) AS note_moy,
            SUM(CASE WHEN a.note >= 7 THEN 1 ELSE 0 END) AS satisfaits,
            SUM(CASE WHEN a.note <= 3 THEN 1 ELSE 0 END) AS insatisfaits
        FROM operateurs o
        LEFT JOIN avis a ON a.operateur_id = o.id AND a.publie = 1
        WHERE o.actif = 1
        GROUP BY o.id
        ORDER BY o.nom
    ");
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rapport_artec_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Operateur', 'Nb avis', 'Note moyenne', 'Satisfaits (>=7)', 'Insatisfaits (<=3)']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['operateur'], $r['nb_avis'], $r['note_moy'], $r['satisfaits'], $r['insatisfaits']]);
    }
    fclose($out);
    log_action('export_rapport', 'rapports', null, 'Export CSV global');
    exit;
}

$stmt = db()->query("
    SELECT
        o.id, o.nom, o.slug, o.couleur,
        COUNT(a.id) AS nb_avis,
        COALESCE(ROUND(AVG(a.note),2),0) AS note_moy,
        SUM(CASE WHEN a.note >= 7 THEN 1 ELSE 0 END) AS satisfaits,
        SUM(CASE WHEN a.note <= 3 THEN 1 ELSE 0 END) AS insatisfaits
    FROM operateurs o
    LEFT JOIN avis a ON a.operateur_id = o.id AND a.publie = 1
    WHERE o.actif = 1
    GROUP BY o.id
    ORDER BY o.nom
");
$rows = $stmt->fetchAll();

$stmt = db()->query("
    SELECT region, COUNT(*) AS nb, ROUND(AVG(note),1) AS moy
    FROM avis WHERE publie = 1
    GROUP BY region ORDER BY nb DESC LIMIT 10
");
$parRegion = $stmt->fetchAll();

$pageTitle = 'Rapports ARTEC';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="fw-bold mb-0"><i class="bi bi-file-earmark-bar-graph text-artec"></i> Rapports</h1>
    <a href="?export=csv" class="btn btn-artec">
        <i class="bi bi-download"></i> Exporter tout en CSV
    </a>
</div>

<div class="card card-artec mb-4">
    <div class="card-header"><i class="bi bi-table"></i> Synthese par operateur</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-artec mb-0">
                <thead>
                    <tr>
                        <th>Operateur</th>
                        <th>Avis</th>
                        <th>Note moyenne</th>
                        <th>Satisfaits (>=7)</th>
                        <th>Insatisfaits (<=3)</th>
                        <th>Taux satisfaction</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r):
                        $taux = $r['nb_avis'] > 0 ? round($r['satisfaits'] / $r['nb_avis'] * 100) : 0;
                    ?>
                        <tr>
                            <td>
                                <span class="badge-operateur badge-<?= e($r['slug']) ?>"><?= e($r['nom']) ?></span>
                            </td>
                            <td><?= (int) $r['nb_avis'] ?></td>
                            <td><strong class="text-artec"><?= number_format((float) $r['note_moy'], 1) ?>/10</strong></td>
                            <td class="text-success"><?= (int) $r['satisfaits'] ?></td>
                            <td class="text-danger"><?= (int) $r['insatisfaits'] ?></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" style="width: <?= $taux ?>%"><?= $taux ?>%</div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-artec">
    <div class="card-header"><i class="bi bi-geo-alt"></i> Top regions par nombre d'avis</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-artec mb-0">
                <thead><tr><th>Region</th><th>Nb avis</th><th>Note moyenne</th></tr></thead>
                <tbody>
                    <?php foreach ($parRegion as $r): ?>
                        <tr>
                            <td><strong><?= e($r['region']) ?></strong></td>
                            <td><?= (int) $r['nb'] ?></td>
                            <td><?= number_format((float) $r['moy'], 1) ?>/10</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>