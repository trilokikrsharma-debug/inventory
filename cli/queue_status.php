<?php
/**
 * InvenBill Pro — Queue Monitoring Script
 * Outputs the current status of the background jobs queue.
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (\Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Fetch stats
$stats = $pdo->query(
    "SELECT status, COUNT(*) as count FROM jobs GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$pending = (int)($stats['pending'] ?? 0);
$processing = (int)($stats['processing'] ?? 0);
$failed = (int)($stats['failed'] ?? 0);
$completed = (int)($stats['completed'] ?? 0);

// Detect jobs stuck in processing for more than 30 minutes
$stuck = $pdo->query(
    "SELECT COUNT(*) FROM jobs WHERE status = 'processing' AND started_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
)->fetchColumn();

echo "=========================================\n";
echo "       QUEUE STATUS MONITOR              \n";
echo "=========================================\n";
echo "Pending Jobs     : {$pending}\n";
echo "Processing Jobs  : {$processing}\n";
echo "Failed Jobs      : {$failed}\n";
echo "Completed Jobs   : {$completed}\n";
echo "-----------------------------------------\n";
echo "Stuck (> 30 min) : " . ($stuck > 0 ? "\033[31m{$stuck}\033[0m" : "0") . "\n";
echo "=========================================\n";
