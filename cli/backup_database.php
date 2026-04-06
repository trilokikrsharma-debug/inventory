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
require_once BASE_PATH . '/services/BackupService.php';

$dbConfig = require CONFIG_PATH . '/database.php';
$backupDir = BackupService::resolveBackupRoot() . '/full';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$date = date('Ymd_His');
$filename = "full_backup_{$date}.sql";
$filepath = $backupDir . '/' . $filename;

$defaultsFile = tempnam(sys_get_temp_dir(), 'mysqldump_');
if ($defaultsFile === false) {
    echo "[BACKUP] ERROR: failed to create temporary credentials file\n";
    exit(1);
}

$defaultsContent = "[client]\n"
    . "host=" . ($dbConfig['host'] ?? '127.0.0.1') . "\n"
    . "port=" . ($dbConfig['port'] ?? '3306') . "\n"
    . "user=" . ($dbConfig['username'] ?? '') . "\n"
    . "password=" . ($dbConfig['password'] ?? '') . "\n";

if (@file_put_contents($defaultsFile, $defaultsContent, LOCK_EX) === false) {
    @unlink($defaultsFile);
    echo "[BACKUP] ERROR: failed to write temporary credentials file\n";
    exit(1);
}
@chmod($defaultsFile, 0600);

$command = [
    'mysqldump',
    '--defaults-extra-file=' . $defaultsFile,
    '--single-transaction',
    '--quick',
    '--skip-lock-tables',
    (string)($dbConfig['database'] ?? ''),
];

echo "[BACKUP] Starting backup sequence for {$dbConfig['database']}...\n";

$stdout = fopen($filepath, 'wb');
if ($stdout === false) {
    @unlink($defaultsFile);
    echo "[BACKUP] ERROR: failed to open backup destination\n";
    exit(1);
}

$stderr = fopen('php://temp', 'w+');
if ($stderr === false) {
    fclose($stdout);
    @unlink($defaultsFile);
    echo "[BACKUP] ERROR: failed to open error stream\n";
    exit(1);
}

$process = proc_open(
    $command,
    [
        0 => ['pipe', 'r'],
        1 => $stdout,
        2 => $stderr,
    ],
    $pipes,
    BASE_PATH
);

if (!is_resource($process)) {
    fclose($stdout);
    fclose($stderr);
    @unlink($defaultsFile);
    @unlink($filepath);
    echo "[BACKUP] ERROR: failed to start mysqldump process\n";
    exit(1);
}

if (isset($pipes[0]) && is_resource($pipes[0])) {
    fclose($pipes[0]);
}

$returnVar = proc_close($process);
rewind($stderr);
$stderrOutput = stream_get_contents($stderr) ?: '';
fclose($stderr);
fclose($stdout);
@unlink($defaultsFile);

if ($returnVar !== 0 || !is_file($filepath) || filesize($filepath) === 0) {
    @unlink($filepath);
    echo "[BACKUP] ERROR: mysqldump failed with code {$returnVar}\n";
    if ($stderrOutput !== '') {
        echo trim($stderrOutput) . "\n";
    }
    error_log("[BACKUP] Failed with code {$returnVar}" . ($stderrOutput !== '' ? ': ' . trim($stderrOutput) : ''));
    exit(1);
}

BackupService::backfillManifestForExistingBackup($filepath, ['backup_type' => 'full']);

echo "[BACKUP] SUCCESS: Saved to {$filename} (" . round(filesize($filepath) / 1024 / 1024, 2) . " MB)\n";

// Cleanup old backups (keep last 30)
$backups = glob($backupDir . '/full_backup_*.sql');
if (count($backups) > 30) {
    usort($backups, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    $toDeleteCount = count($backups) - 30;
    
    for ($i = 0; $i < $toDeleteCount; $i++) {
        unlink($backups[$i]);
        BackupService::deleteManifest($backups[$i]);
        echo "[BACKUP] CLEANUP: Deleted old backup " . basename($backups[$i]) . "\n";
    }
}

exit(0);
