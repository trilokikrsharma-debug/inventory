<?php
/**
 * Cron: Cleanup stale rate-limit and cache files
 *
 * Run daily via cron:
 *   0 3 * * * php /path/to/inventory/cli/cleanup_cache.php
 *
 * Cleans:
 *   - Stale rate-limit files in /cache/
 *   - Old log files beyond retention
 */

define('BASE_PATH', dirname(__DIR__));

$cacheDir = BASE_PATH . '/cache';
$logDir = BASE_PATH . '/logs';

// ─── Cleanup Rate Limit Files (older than 2 hours) ──────────
$threshold = time() - 7200;
$cleaned = 0;

if (is_dir($cacheDir)) {
    foreach (glob($cacheDir . '/ratelimit_*.json') as $file) {
        if (filemtime($file) < $threshold) {
            @unlink($file);
            $cleaned++;
        }
    }

    // Also clean billing rate-limit files
    foreach (glob($cacheDir . '/billing_*.json') as $file) {
        if (filemtime($file) < $threshold) {
            @unlink($file);
            $cleaned++;
        }
    }

    // Clean generic rate-limit files
    foreach (glob($cacheDir . '/rate_*.json') as $file) {
        if (filemtime($file) < $threshold) {
            @unlink($file);
            $cleaned++;
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Cache cleanup: removed {$cleaned} stale files\n";

// ─── Cleanup Old Logs (older than 30 days) ──────────────────
$logThreshold = time() - (30 * 86400);
$logsCleaned = 0;

if (is_dir($logDir)) {
    foreach (glob($logDir . '/*.log.*') as $file) {
        if (filemtime($file) < $logThreshold) {
            @unlink($file);
            $logsCleaned++;
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Log cleanup: removed {$logsCleaned} old log files\n";

// ─── Rotate Large Log Files ─────────────────────────────────
$maxLogSize = 50 * 1024 * 1024; // 50MB

if (is_dir($logDir)) {
    foreach (glob($logDir . '/*.log') as $logFile) {
        if (file_exists($logFile) && filesize($logFile) > $maxLogSize) {
            $rotated = $logFile . '.' . date('Y-m-d-His');
            rename($logFile, $rotated);
            touch($logFile);
            echo "[" . date('Y-m-d H:i:s') . "] Rotated: " . basename($logFile) . " -> " . basename($rotated) . "\n";
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Cleanup complete.\n";
