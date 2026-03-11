<?php
/**
 * Webhook Dispatcher — Event Notifications for Integrations
 * 
 * Sends HTTP POST notifications to registered webhook URLs when
 * business events occur (sale created, payment received, etc.).
 * 
 * Features:
 *   - HMAC-SHA256 signed payloads (Stripe-style)
 *   - Async dispatch via file queue (Redis upgrade path)
 *   - Automatic retry with exponential backoff
 *   - Per-tenant webhook registration
 *   - Event filtering (subscribe to specific events only)
 * 
 * Usage:
 *   WebhookDispatcher::dispatch('sale.created', [
 *       'sale_id' => 42, 'total' => 1500, 'customer' => 'John'
 *   ]);
 */
class WebhookDispatcher {

    // Events that can trigger webhooks
    const EVENTS = [
        'sale.created', 'sale.updated', 'sale.deleted',
        'purchase.created', 'purchase.updated',
        'payment.received', 'payment.sent',
        'product.created', 'product.updated', 'product.low_stock',
        'customer.created', 'customer.updated',
        'invoice.generated',
        'backup.completed',
    ];

    /**
     * Dispatch a webhook event to all registered endpoints for this tenant
     * 
     * @param string $event   Event name (e.g., 'sale.created')
     * @param array  $payload Event data
     * @param int|null $companyId Override tenant (defaults to current)
     */
    public static function dispatch(string $event, array $payload, ?int $companyId = null): void {
        $cid = $companyId ?? (class_exists('Tenant') ? \Tenant::id() : null);
        if (!$cid) return;

        // Check if webhooks feature is enabled
        if (class_exists('FeatureFlag') && !FeatureFlag::isEnabled('webhooks')) {
            return;
        }

        try {
            $db = Database::getInstance();
            $hooks = $db->query(
                "SELECT id, url, secret, events FROM webhooks 
                 WHERE company_id = ? AND is_active = 1",
                [$cid]
            )->fetchAll();

            foreach ($hooks as $hook) {
                // Check if this hook subscribes to this event
                $subscribedEvents = json_decode($hook['events'], true) ?: ['*'];
                if (!in_array('*', $subscribedEvents) && !in_array($event, $subscribedEvents)) {
                    continue;
                }

                // Queue the delivery (non-blocking)
                self::queueDelivery($hook, $event, $payload);
            }
        } catch (\Exception $e) {
            Logger::error('Webhook dispatch failed', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Queue a webhook delivery for async processing
     */
    private static function queueDelivery(array $hook, string $event, array $payload): void {
        $job = [
            'webhook_id' => $hook['id'],
            'url'        => $hook['url'],
            'secret'     => $hook['secret'],
            'event'      => $event,
            'payload'    => $payload,
            'attempt'    => 1,
            'created_at' => time(),
        ];

        $queueDir = defined('BASE_PATH') ? BASE_PATH . '/cache/webhook_queue' : __DIR__ . '/../cache/webhook_queue';
        if (!is_dir($queueDir)) @mkdir($queueDir, 0755, true);

        $file = $queueDir . '/' . uniqid('wh_', true) . '.json';
        @file_put_contents($file, json_encode($job), LOCK_EX);
    }

    /**
     * Process queued webhook deliveries (call from cron or worker)
     * 
     * Cron: * / 1 * * * * php /path/to/inventory/cli/process_webhooks.php
     */
    public static function processQueue(int $batchSize = 50): int {
        $queueDir = defined('BASE_PATH') ? BASE_PATH . '/cache/webhook_queue' : __DIR__ . '/../cache/webhook_queue';
        if (!is_dir($queueDir)) return 0;

        $files = glob($queueDir . '/wh_*.json');
        $processed = 0;

        foreach (array_slice($files, 0, $batchSize) as $file) {
            $raw = @file_get_contents($file);
            if (!$raw) { @unlink($file); continue; }

            $job = json_decode($raw, true);
            if (!$job) { @unlink($file); continue; }

            $success = self::deliver($job);
            
            if ($success) {
                @unlink($file);
                $processed++;
            } else {
                // Retry with exponential backoff (max 3 attempts)
                $job['attempt'] = ($job['attempt'] ?? 1) + 1;
                if ($job['attempt'] > 3) {
                    // Move to dead letter
                    $dlq = $queueDir . '/failed';
                    if (!is_dir($dlq)) @mkdir($dlq, 0755, true);
                    @rename($file, $dlq . '/' . basename($file));
                    Logger::warning('Webhook delivery permanently failed', [
                        'webhook_id' => $job['webhook_id'], 'url' => $job['url'], 'event' => $job['event']
                    ]);
                } else {
                    @file_put_contents($file, json_encode($job), LOCK_EX);
                }
            }
        }

        return $processed;
    }

    /**
     * Deliver a webhook payload to the endpoint
     */
    private static function deliver(array $job): bool {
        $payload = json_encode([
            'event'      => $job['event'],
            'data'       => $job['payload'],
            'webhook_id' => $job['webhook_id'],
            'timestamp'  => time(),
        ]);

        // Generate HMAC signature (Stripe-style)
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = 'v1=' . hash_hmac('sha256', $signedPayload, $job['secret'] ?? '');

        $headers = [
            'Content-Type: application/json',
            'X-Webhook-Signature: ' . $signature,
            'X-Webhook-Timestamp: ' . $timestamp,
            'X-Webhook-Event: ' . $job['event'],
            'User-Agent: InvenBill-Webhook/1.0',
        ];

        $ch = curl_init($job['url']);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $success = $httpCode >= 200 && $httpCode < 300;

        // Log delivery attempt
        try {
            $db = Database::getInstance();
            $db->query(
                "INSERT INTO webhook_deliveries (webhook_id, event, payload, response_code, response_body, success, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$job['webhook_id'], $job['event'], $payload, $httpCode, substr($response ?? '', 0, 500), $success ? 1 : 0]
            );
        } catch (\Exception $e) { /* non-critical logging */ }

        return $success;
    }

    /**
     * Register a webhook for a tenant
     */
    public static function register(int $companyId, string $url, array $events = ['*']): array {
        $secret = bin2hex(random_bytes(32));
        
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO webhooks (company_id, url, secret, events, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())",
            [$companyId, $url, $secret, json_encode($events)]
        );

        Logger::audit('webhook_registered', 'webhooks', $db->lastInsertId(), [
            'url' => $url, 'events' => $events
        ]);

        return ['id' => $db->lastInsertId(), 'secret' => $secret];
    }
}
