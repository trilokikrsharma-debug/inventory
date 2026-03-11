<?php
/**
 * Job Dispatcher — Queue Background Tasks
 * 
 * Pushes jobs to the `jobs` database table for async processing.
 * Jobs are picked up by the cli/worker.php script.
 * 
 * Usage:
 *   JobDispatcher::dispatch('email', 'SendInvoiceEmail', ['sale_id' => 123]);
 *   JobDispatcher::dispatch('webhook', 'DeliverWebhook', $payload, priority: 1);
 *   JobDispatcher::later('backup', 'ProcessBackup', $data, delay: 300);
 */
class JobDispatcher {
    /**
     * Dispatch a job to the queue.
     *
     * @param string $queue     Queue name (default, email, webhook, backup)
     * @param string $handler   Class or callable name to handle the job
     * @param array  $payload   Job data
     * @param int    $priority  1 (highest) to 10 (lowest)
     * @param int    $maxAttempts  Max retry attempts on failure
     * @return int   Job ID
     */
    public static function dispatch(
        string $queue,
        string $handler,
        array $payload = [],
        int $priority = 5,
        int $maxAttempts = 3
    ): int {
        $user = Session::get('user');
        $companyId = $user['company_id'] ?? (defined('TENANT_ID') ? TENANT_ID : 0);

        $db = Database::getInstance();
        $db->query(
            "INSERT INTO `jobs` (`company_id`, `queue`, `handler`, `payload`, `priority`, `max_attempts`)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$companyId, $queue, $handler, json_encode($payload), $priority, $maxAttempts]
        );

        return (int)$db->getConnection()->lastInsertId();
    }

    /**
     * Dispatch a delayed job.
     *
     * @param string $queue    Queue name
     * @param string $handler  Handler name
     * @param array  $payload  Job data
     * @param int    $delay    Delay in seconds from now
     * @return int   Job ID
     */
    public static function later(
        string $queue,
        string $handler,
        array $payload = [],
        int $delay = 60,
        int $priority = 5
    ): int {
        $user = Session::get('user');
        $companyId = $user['company_id'] ?? (defined('TENANT_ID') ? TENANT_ID : 0);
        $scheduledAt = date('Y-m-d H:i:s', time() + $delay);

        $db = Database::getInstance();
        $db->query(
            "INSERT INTO `jobs` (`company_id`, `queue`, `handler`, `payload`, `priority`, `max_attempts`, `scheduled_at`)
             VALUES (?, ?, ?, ?, ?, 3, ?)",
            [$companyId, $queue, $handler, json_encode($payload), $priority, $scheduledAt]
        );

        return (int)$db->getConnection()->lastInsertId();
    }

    /**
     * Get queue stats for a company.
     */
    public static function stats(?int $companyId = null): array {
        $user = Session::get('user');
        $companyId = $companyId ?? ($user['company_id'] ?? 0);

        $db = Database::getInstance();
        $result = $db->query(
            "SELECT status, COUNT(*) as count FROM `jobs` WHERE company_id = ? GROUP BY status",
            [$companyId]
        )->fetchAll(\PDO::FETCH_KEY_PAIR);

        return [
            'pending'    => (int)($result['pending'] ?? 0),
            'processing' => (int)($result['processing'] ?? 0),
            'completed'  => (int)($result['completed'] ?? 0),
            'failed'     => (int)($result['failed'] ?? 0),
        ];
    }
}
