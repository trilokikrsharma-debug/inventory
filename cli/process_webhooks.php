<?php
/**
 * Webhook Queue Worker
 * 
 * Processes pending webhook deliveries from the file-based queue.
 * 
 * Usage:
 *   php cli/process_webhooks.php              # Process once (cron mode)
 *   php cli/process_webhooks.php --daemon     # Continuous worker
 * 
 * Cron (every minute):
 *   * * * * * php /path/to/inventory/cli/process_webhooks.php
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Session.php';
require_once CORE_PATH . '/Tenant.php';
require_once CORE_PATH . '/Logger.php';
require_once CORE_PATH . '/FeatureFlag.php';
require_once CORE_PATH . '/WebhookDispatcher.php';

$daemon = in_array('--daemon', $argv ?? []);

echo "[" . date('c') . "] Webhook worker started" . ($daemon ? ' (daemon mode)' : '') . "\n";

do {
    $processed = WebhookDispatcher::processQueue(50);
    
    if ($processed > 0) {
        echo "[" . date('c') . "] Processed {$processed} webhook(s)\n";
    }
    
    if ($daemon) {
        sleep(5); // Check every 5 seconds
    }
} while ($daemon);

echo "[" . date('c') . "] Done.\n";
