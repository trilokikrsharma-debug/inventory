<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=invenbill;charset=utf8mb4", 'invenbill_app', 'InvenBillPass@2026', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Show companies columns
$cols = $pdo->query("SHOW COLUMNS FROM companies")->fetchAll(PDO::FETCH_ASSOC);
echo "=== COMPANIES COLUMNS ===\n";
foreach ($cols as $c) {
    echo $c['Field'] . " | " . $c['Type'] . " | Null=" . $c['Null'] . " | Default=" . $c['Default'] . "\n";
}

// Show all tables
echo "\n=== ALL TABLES ===\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) echo $t . "\n";

// Show RateLimiter
echo "\n=== CHECKING RATELIMITER ===\n";
$rl = file_get_contents('/var/www/inventory/core/RateLimiter.php') ?: 'not found';
echo substr($rl, 0, 500) . "\n";
