<?php
/**
 * InvenBill Pro — Queue Worker (CLI)
 * 
 * Processes background jobs from the `jobs` table.
 * Runs as a long-lived process or via cron.
 * 
 * Usage:
 *   php cli/worker.php                     Process all queues
 *   php cli/worker.php --queue=email       Process only email queue
 *   php cli/worker.php --once              Process one job and exit
 *   php cli/worker.php --daemon            Run infinitely (restarts memory after 100 jobs)
 *   php cli/worker.php --cleanup           Remove completed jobs older than 7 days
 * 
 * Example Cron (Daemon restart if stopped):
 * * * * * * /usr/bin/php /path/to/inventory/cli/worker.php --daemon >> /path/to/inventory/logs/worker.log 2>&1
 */

// ─── Bootstrap ───────────────────────────────────────────
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

// Parse CLI arguments
$args = [];
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--')) {
        $parts = explode('=', substr($arg, 2), 2);
        $args[$parts[0]] = $parts[1] ?? true;
    }
}

$queue = $args['queue'] ?? null;
$once = isset($args['once']);
$daemon = isset($args['daemon']);
$cleanup = isset($args['cleanup']);

echo "[Worker] InvenBill Pro Queue Worker started" . PHP_EOL;
echo "[Worker] Queue: " . ($queue ?: 'all') . " | Mode: " . ($daemon ? 'daemon' : ($once ? 'single' : 'loop')) . PHP_EOL;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (\Exception $e) {
    echo "[Worker] Database connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// ─── Cleanup Mode ────────────────────────────────────────
if ($cleanup) {
    $days = (int)($args['days'] ?? 7);
    $stmt = $pdo->prepare(
        "DELETE FROM `jobs` WHERE status IN ('completed','failed') AND completed_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
    );
    $stmt->execute([$days]);
    $cleaned = $stmt->rowCount();
    echo "[Worker] Cleaned {$cleaned} old jobs (>{$days} days)" . PHP_EOL;
    exit(0);
}

// ─── Worker Loop ─────────────────────────────────────────
$processed = 0;
$maxJobs = $once ? 1 : ($daemon ? 100 : PHP_INT_MAX); // Reset after 100 jobs in daemon mode
$sleepInterval = 2; // seconds between polling

while ($processed < $maxJobs) {
    // Claim a job atomically (SELECT ... FOR UPDATE pattern)
    try {
        $pdo->beginTransaction();

        $queueFilter = $queue ? "AND queue = ?" : "";
        $params = $queue ? [$queue] : [];

        $stmt = $pdo->prepare(
            "SELECT * FROM `jobs` 
             WHERE status = 'pending' 
               AND (scheduled_at IS NULL OR scheduled_at <= NOW())
               AND attempts < max_attempts 
               {$queueFilter}
             ORDER BY priority ASC, created_at ASC
             LIMIT 1
             FOR UPDATE SKIP LOCKED"
        );
        $stmt->execute($params);
        $job = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$job) {
            $pdo->rollBack();
            if ($once) break;
            sleep($sleepInterval);
            continue;
        }

        // Mark as processing
        $pdo->prepare(
            "UPDATE `jobs` SET status = 'processing', started_at = NOW(), attempts = attempts + 1 WHERE id = ?"
        )->execute([$job['id']]);

        $pdo->commit();

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("[Worker] Job claim failed: " . $e->getMessage());
        sleep($sleepInterval);
        continue;
    }

    // Execute the job
    echo "[Worker] Processing job #{$job['id']} ({$job['handler']}) ... ";

    try {
        $payload = json_decode($job['payload'], true) ?: [];
        $handler = $job['handler'];

        // ─── SECURITY FIX (QUEUE-3): Handler Allowlist ───────────
        // CRITICAL: Only whitelisted handlers may execute.
        // The old code used is_callable() + call_user_func(), which
        // allowed arbitrary PHP functions (system, exec, passthru)
        // to run if an attacker could insert a row into `jobs`.
        //
        // To add a new handler:
        //   1. Add the class name or Class::method string below
        //   2. Ensure the class file is loaded by the autoloader
        $allowedHandlers = [
            // Email handlers (used by EmailService)
            'EmailService::processEmail',
            'EmailService::processInvoiceEmail',
            // Webhook delivery
            'DeliverWebhook',
            // Backup processing
            'ProcessBackup',
            // Report export generation
            'GenerateReportExport',
            // Invoice email (alias)
            'SendInvoiceEmail',
            // Add new job handlers here ↓
        ];

        if (!in_array($handler, $allowedHandlers, true)) {
            error_log("[Worker] SECURITY: Blocked unauthorized handler '{$handler}' in job #{$job['id']}");
            Helper::securityLog('QUEUE_HANDLER_BLOCKED', "Unauthorized handler: {$handler} (job #{$job['id']})");
            throw new \RuntimeException("Handler not in allowlist: {$handler}");
        }

        // ─── SECURITY FIX (QUEUE-4): Set tenant context ─────────
        // Without this, Model queries inside handlers run unscoped.
        $jobCompanyId = (int)($job['company_id'] ?? 0);
        if ($jobCompanyId > 0) {
            Tenant::set($jobCompanyId);
        }

        // Execute: Class::staticMethod or Class with handle() method
        if (str_contains($handler, '::')) {
            // Static method call — e.g. "EmailService::processEmail"
            [$className, $methodName] = explode('::', $handler, 2);
            if (class_exists($className) && method_exists($className, $methodName)) {
                $className::$methodName($payload, $job);
            } else {
                throw new \RuntimeException("Handler class/method not found: {$handler}");
            }
        } elseif (class_exists($handler) && method_exists($handler, 'handle')) {
            // Class with handle() method — e.g. "DeliverWebhook"
            $handler::handle($payload, $job);
        } else {
            throw new \RuntimeException("Handler not found: {$handler}");
        }

        // Mark completed
        $pdo->prepare(
            "UPDATE `jobs` SET status = 'completed', completed_at = NOW(), error = NULL WHERE id = ?"
        )->execute([$job['id']]);

        echo "OK" . PHP_EOL;

    } catch (\Exception $e) {
        // Mark as failed or pending for retry
        $newStatus = ($job['attempts'] + 1 >= $job['max_attempts']) ? 'failed' : 'pending';
        $pdo->prepare(
            "UPDATE `jobs` SET status = ?, error = ?, completed_at = CASE WHEN ? = 'failed' THEN NOW() ELSE NULL END WHERE id = ?"
        )->execute([$newStatus, $e->getMessage(), $newStatus, $job['id']]);

        echo "FAILED ({$e->getMessage()})" . PHP_EOL;
        Logger::log(Logger::ERROR, 'Queue Job Failed', ['job_id' => $job['id'], 'handler' => $job['handler'], 'error' => $e->getMessage()], Logger::CHANNEL_QUEUE);
    }

    $processed++;
}

echo "[Worker] Processed {$processed} job(s). Exiting." . PHP_EOL;
exit(0);
