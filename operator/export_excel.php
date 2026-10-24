<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('operateur');

$user = current_user();
$opId = $user['operateur_id'];

$stmt = db()->prepare("
    SELECT a.id, a.note, a.commentaire, a.region, a.service, a.created_at, p.titre AS publication
    FROM avis a
    LEFT JOIN publications p ON p.id = a.publication_id
    WHERE a.operateur_id = ? AND a.publie = 1
    ORDER BY a.created_at DESC
");
$stmt->execute([$opId]);
$avis = $stmt->fetchAll();

$filename = 'avis_' . preg_replace('/[^a-z0-9]+/', '_', $user['operateur_slug']) . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['ID', 'Note', 'Commentaire', 'Region', 'Service', 'Publication', 'Date']);
foreach ($avis as $a) {
    fputcsv($out, [
        $a['id'],
        $a['note'],
        $a['commentaire'] ?? '',
        $a['region'],
        $a['service'],
        $a['publication'] ?? '',
        date('d/m/Y H:i', strtotime($a['created_at'])),
    ]);
}
fclose($out);

log_action('export_avis', 'avis', null, count($avis) . ' avis exportes en CSV');
exit;