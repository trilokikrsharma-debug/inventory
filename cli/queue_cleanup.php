<?php
/**
 * InvenBill Pro — Queue Cleanup
 * 
 * Removes old processed jobs from the `jobs` database table.
 * Deletes completed jobs older than 7 days, and failed jobs older than 30 days.
 * 
 * Example Cron (Run Daily at 3:00 AM):
 * 0 3 * * * /usr/bin/php /path/to/inventory/cli/queue_cleanup.php >> /path/to/inventory/logs/cron.log 2>&1
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

echo "[CLEANUP] Starting queue cleanup...\n";

// Delete completed jobs older than 7 days
$stmt1 = $pdo->prepare("DELETE FROM `jobs` WHERE status = 'completed' AND completed_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt1->execute();
$completedDeleted = $stmt1->rowCount();

// Delete failed jobs older than 30 days
$stmt2 = $pdo->prepare("DELETE FROM `jobs` WHERE status = 'failed' AND (completed_at < DATE_SUB(NOW(), INTERVAL 30 DAY) OR created_at < DATE_SUB(NOW(), INTERVAL 30 DAY))");
$stmt2->execute();
$failedDeleted = $stmt2->rowCount();

echo "[CLEANUP] Success. Removed {$completedDeleted} completed jobs and {$failedDeleted} failed jobs.\n";
exit(0);
