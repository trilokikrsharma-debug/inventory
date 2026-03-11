<?php
/**
 * InvenBill Pro — Automated Database Backup
 * 
 * Dumps the full MySQL database to the /backups directory.
 * Retains only the 30 most recent backups to save disk space.
 * 
 * Example Cron (Run Daily at 2:00 AM):
 * 0 2 * * * /usr/bin/php /path/to/inventory/cli/backup_database.php >> /path/to/inventory/logs/cron.log 2>&1
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

$dbConfig = require CONFIG_PATH . '/database.php';
$backupDir = BASE_PATH . '/backups';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    file_put_contents($backupDir . '/index.html', ''); // Prevent directory listing
}

$date = date('Ymd_His');
$filename = "backup_{$date}.sql";
$filepath = $backupDir . '/' . $filename;

// Construct mysqldump command
// Note: In production you may omit the password inline and use my.cnf for better security
$host = escapeshellarg($dbConfig['host']);
$port = escapeshellarg($dbConfig['port']);
$user = escapeshellarg($dbConfig['username']);
$pass = escapeshellarg($dbConfig['password']);
$dbname = escapeshellarg($dbConfig['database']);
$dest = escapeshellarg($filepath);

$cmd = "mysqldump --host={$host} --port={$port} --user={$user} --password={$pass} {$dbname} > {$dest}";

echo "[BACKUP] Starting backup sequence for {$dbConfig['database']}...\n";
exec($cmd, $output, $returnVar);

if ($returnVar !== 0) {
    echo "[BACKUP] ERROR: mysqldump failed with code {$returnVar}\n";
    error_log("[BACKUP] Failed with code {$returnVar}");
    exit(1);
}

echo "[BACKUP] SUCCESS: Saved to {$filename} (" . round(filesize($filepath) / 1024 / 1024, 2) . " MB)\n";

// Cleanup old backups (keep last 30)
$backups = glob($backupDir . '/backup_*.sql');
if (count($backups) > 30) {
    usort($backups, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    $toDeleteCount = count($backups) - 30;
    
    for ($i = 0; $i < $toDeleteCount; $i++) {
        unlink($backups[$i]);
        echo "[BACKUP] CLEANUP: Deleted old backup " . basename($backups[$i]) . "\n";
    }
}

exit(0);
