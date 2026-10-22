<?php
require_once __DIR__ . '/../includes/functions.php';
$rows = db()->query("SELECT operator_code, COUNT(*) AS review_count, ROUND(AVG(rating), 2) AS average_rating FROM reviews WHERE status = 'published' GROUP BY operator_code")->fetchAll();
json_response(['ok' => true, 'data' => $rows]);