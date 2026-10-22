<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "✅ Config OK<br>";
echo "BASE_URL = " . BASE_URL . "<br>";

try {
    $count = db()->query("SELECT COUNT(*) FROM operateurs")->fetchColumn();
    echo "✅ BDD connectée — {$count} opérateurs<br>";
} catch (Throwable $e) {
    echo "❌ BDD : " . $e->getMessage();
}

$pageTitle = "Test";
require_once __DIR__ . '/includes/header.php';
echo "<h1 class='text-artec'>Test du socle — si tu vois ça, c'est bon 🎉</h1>";
require_once __DIR__ . '/includes/footer.php';