<?php
/**
 * InvenBill lightweight scheduler for production cron/systemd timers.
 *
 * Tasks:
 *   --task=cleanup-jobs     : cleanup old completed/failed jobs
 *   --task=queue-backups    : queue daily tenant backups
 *   --task=all              : cleanup + queue-backups
 *
 * Optional:
 *   --company=123           : only queue backup for a specific company
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

final class Scheduler {
    public function run(array $args): int {
        $task = $args['task'] ?? 'all';
        $companyId = isset($args['company']) ? (int)$args['company'] : 0;

        try {
            if ($task === 'cleanup-jobs' || $task === 'all') {
                $this->cleanupJobs(14);
            }
            if ($task === 'queue-backups' || $task === 'all') {
                $this->queueBackups($companyId > 0 ? $companyId : null);
            }
            $this->out('[SCHEDULER] Completed task=' . $task);
            return 0;
        } catch (Throwable $e) {
            $this->out('[SCHEDULER] Failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function cleanupJobs(int $days): void {
        $days = max(1, $days);
        $db = Database::getInstance();
        $db->query(
            "DELETE FROM `jobs`
             WHERE status IN ('completed','failed')
               AND completed_at IS NOT NULL
               AND completed_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
        $deleted = $db->rowCount();
        $this->out('[SCHEDULER] cleanup-jobs deleted=' . (int)$deleted . ' days=' . $days);
    }

    private function queueBackups(?int $companyId = null): void {
        $db = Database::getInstance();
        $today = date('Y-m-d');

        if ($companyId !== null) {
            $companies = [['id' => $companyId]];
        } else {
            $companies = $db->query(
                "SELECT id
                 FROM companies
                 WHERE status = 'active'"
            )->fetchAll();

            if (empty($companies)) {
                // Fallback for installations without status column values.
                $companies = $db->query("SELECT id FROM companies")->fetchAll();
            }
        }

        $queued = 0;
        foreach ($companies as $company) {
            $cid = (int)($company['id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }

            $alreadyQueuedToday = (int)$db->query(
                "SELECT COUNT(*)
                 FROM jobs
                 WHERE queue = 'backup'
                   AND handler = 'ProcessBackup'
                   AND company_id = ?
                   AND DATE(created_at) = ?",
                [$cid, $today]
            )->fetchColumn();

            if ($alreadyQueuedToday > 0) {
                continue;
            }

            $payload = json_encode([
                'company_id' => $cid,
                'backup_type' => 'tenant',
                'is_super_admin' => false,
                'user_id' => 0,
                'requested_at' => date('Y-m-d H:i:s'),
                'origin' => 'scheduler',
            ], JSON_UNESCAPED_UNICODE);

            $db->query(
                "INSERT INTO jobs (company_id, queue, handler, payload, priority, max_attempts, status)
                 VALUES (?, 'backup', 'ProcessBackup', ?, 4, 2, 'pending')",
                [$cid, $payload]
            );
            $queued++;
        }

        $this->out('[SCHEDULER] queue-backups queued=' . $queued);
    }

    private function out(string $line): void {
        echo $line . PHP_EOL;
    }
}

function parseCliArgs(array $argv): array {
    $args = [];
    foreach ($argv as $arg) {
        if (!str_starts_with($arg, '--')) {
            continue;
        }
        $chunk = substr($arg, 2);
        if ($chunk === '') {
            continue;
        }
        $parts = explode('=', $chunk, 2);
        $args[$parts[0]] = $parts[1] ?? true;
    }
    return $args;
}

$scheduler = new Scheduler();
exit($scheduler->run(parseCliArgs($argv)));
