<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Rapports', 'Exportez les indicateurs de l’observatoire.', 'rapports.php', 'artec');
if (($_GET['format'] ?? '') === 'csv') {
    $rows = db()->query("SELECT operator_code, COUNT(*) AS review_count, ROUND(AVG(rating),2) AS average_rating, SUM(status='flagged') AS flagged_count FROM reviews GROUP BY operator_code")->fetchAll();
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="rapport-artec-' . date('Y-m-d') . '.csv"'); echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w'); fputcsv($out, ['Opérateur', 'Avis', 'Note moyenne', 'Signalés'], ';'); foreach ($rows as $row) fputcsv($out, $row, ';'); fclose($out); exit;
}
$rows = db()->query("SELECT operator_code, COUNT(*) AS review_count, ROUND(AVG(rating),2) AS average_rating, SUM(status='flagged') AS flagged_count FROM reviews GROUP BY operator_code")->fetchAll();
?>
<div class="panel"><h2>Rapport de performance opérateurs</h2><p>Consolidation des avis enregistrés dans l’observatoire.</p><a class="button" href="<?= e(url('artec/rapports.php?format=csv')) ?>">Télécharger le rapport CSV</a> <button class="button secondary" onclick="window.print()" type="button">Imprimer / PDF</button></div><div class="panel table-wrap" style="margin-top:18px"><table><thead><tr><th>Opérateur</th><th>Nombre d’avis</th><th>Note moyenne / 10</th><th>Avis signalés</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e(ucfirst($row['operator_code'])) ?></td><td><?= (int) $row['review_count'] ?></td><td><?= $row['average_rating'] !== null ? e($row['average_rating']) : '—' ?></td><td><?= (int) $row['flagged_count'] ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php workspace_end(); ?>