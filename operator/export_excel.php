<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('operator');
$code = current_user()['operator_code'];
$statement = db()->prepare("SELECT p.title, r.rating, r.comment, r.region, r.service, r.visitor_name, r.created_at, r.operator_reply FROM reviews r JOIN publications p ON p.id=r.publication_id WHERE r.operator_code=? AND r.status='published' ORDER BY r.created_at DESC");
$statement->execute([$code]);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="avis-' . $code . '-' . date('Y-m-d') . '.csv"');
echo "\xEF\xBB\xBF";
$output = fopen('php://output', 'w');
fputcsv($output, ['Publication', 'Note / 10', 'Commentaire', 'Région', 'Service', 'Visiteur', 'Date', 'Réponse opérateur'], ';');
foreach ($statement->fetchAll() as $row) {
    foreach ($row as &$value) {
        $value = (string) $value;
        if (preg_match('/^[=+\-@\t\r]/', $value)) $value = "'" . $value;
    }
    unset($value);
    fputcsv($output, $row, ';');
}
fclose($output);