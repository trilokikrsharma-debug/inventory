<?php
/**
 * CLI Routine: Activity Log Cleanup
 * 
 * Target: Hard-deletes activity logs older than 90 days.
 * Safe batching via LIMIT to prevent table locks and memory exhaustion.
 * Execution: run via `php cli/cleanup_logs.php`
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

// Let's bootstrap the core system
require_once BASE_PATH . '/core/Database.php';

try {
    $db = Database::getInstance();
    echo "Starting hybrid activity_log cleanup...\n";

    $batchSize = 1000;
    $totalDeleted = 0;
    
    while (true) {
        // Safer indexed batching (ORDER BY primary key with LIMIT) to prevent table locks
        $stmt = $db->query(
            "DELETE FROM activity_log WHERE id IN (
                SELECT id FROM (
                    SELECT id FROM activity_log 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) 
                    ORDER BY id ASC LIMIT {$batchSize}
                ) as tmp
            )"
        );
        $deleted = $stmt->rowCount();
        $totalDeleted += $deleted;
        
        if ($deleted > 0) {
            echo "Deleted {$deleted} obsolete logs in this batch...\n";
            usleep(100000); // 100ms pause to allow DB breathing
        } else {
            break;
        }
    }

    echo "Cleanup complete. Total logs purged: {$totalDeleted}\n";
} catch (Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
}
