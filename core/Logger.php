<?php
/**
 * Structured JSON Logger — Enterprise Observability
 * 
 * Produces machine-parseable JSON logs with tenant/user context.
 * Compatible with ELK Stack, CloudWatch, Datadog, Grafana Loki.
 * 
 * Features:
 *   - Channel-based output (app.log, security.log, queue.log)
 *   - Request ID correlation across all log entries
 *   - Automatic tenant/user/IP context injection
 *   - Severity levels: DEBUG, INFO, WARNING, ERROR, CRITICAL
 *   - Performance timing support
 *   - Non-blocking (never throws, never crashes main flow)
 * 
 * Usage:
 *   Logger::info('Sale created', ['sale_id' => 42, 'total' => 1500]);
 *   Logger::error('Payment failed', ['gateway' => 'razorpay', 'code' => 'TIMEOUT']);
 *   Logger::security('Brute force detected', ['ip' => '1.2.3.4']);
 *   Logger::queue('Job processed', ['job_id' => 456]);
 *   Logger::metric('api_response_time', 0.035);
 */
class Logger {
    const DEBUG    = 'DEBUG';
    const INFO     = 'INFO';
    const WARNING  = 'WARNING';
    const ERROR    = 'ERROR';
    const CRITICAL = 'CRITICAL';

    private static ?string $logDir = null;
    private static string $minLevel = self::INFO;
    private static ?string $requestId = null;

    private static array $levelPriority = [
        'DEBUG'    => 0,
        'INFO'     => 1,
        'WARNING'  => 2,
        'ERROR'    => 3,
        'CRITICAL' => 4,
    ];

    // ─── Channel Constants ───────────────────────────────
    private const CHANNEL_APP      = 'app';
    private const CHANNEL_SECURITY = 'security';
    private const CHANNEL_QUEUE    = 'queue';
    public  const CHANNEL_ERROR    = 'error';

    private static function getDir(): string {
        if (self::$logDir === null) {
            self::$logDir = defined('LOG_PATH') ? LOG_PATH : (defined('BASE_PATH') ? BASE_PATH . '/logs' : __DIR__ . '/../logs');
            if (!is_dir(self::$logDir)) {
                @mkdir(self::$logDir, 0755, true);
            }
        }
        return self::$logDir;
    }

    /**
     * Get or create a unique request ID for log correlation.
     */
    public static function getRequestId(): string {
        if (self::$requestId === null) {
            self::$requestId = substr(bin2hex(random_bytes(8)), 0, 16);
        }
        return self::$requestId;
    }

    /**
     * Set a custom request ID (useful for CLI/worker contexts).
     */
    public static function setRequestId(string $id): void {
        self::$requestId = $id;
    }

    /**
     * Set minimum log level (e.g., in production, set to WARNING)
     */
    public static function setMinLevel(string $level): void {
        self::$minLevel = strtoupper($level);
    }

    /**
     * Core logging method — writes structured JSON to channel-based files.
     */
    public static function log(string $level, string $message, array $context = [], string $channel = self::CHANNEL_APP): void {
        try {
            // Skip if below minimum level (security events always logged)
            if ($channel !== self::CHANNEL_SECURITY) {
                $levelNum = self::$levelPriority[$level] ?? 1;
                $minNum = self::$levelPriority[self::$minLevel] ?? 1;
                if ($levelNum < $minNum) return;
            }

            $entry = [
                'timestamp'  => date('c'),
                'level'      => $level,
                'channel'    => $channel,
                'request_id' => self::getRequestId(),
                'message'    => $message,
                'tenant_id'  => class_exists('Tenant', false) ? \Tenant::id() : null,
                'user_id'    => null,
                'company_id' => null,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                'method'     => $_SERVER['REQUEST_METHOD'] ?? null,
                'uri'        => $_SERVER['REQUEST_URI'] ?? null,
            ];

            // Add user context
            try {
                if (class_exists('Session', false)) {
                    $user = Session::get('user');
                    if ($user) {
                        $entry['user_id'] = $user['id'] ?? null;
                        $entry['company_id'] = $user['company_id'] ?? null;
                    }
                }
            } catch (\Exception $e) {}

            if (!empty($context)) {
                $entry['context'] = $context;
            }

            // Add memory usage for ERROR+ levels
            $levelNum = self::$levelPriority[$level] ?? 1;
            if ($levelNum >= 3) {
                $entry['memory_mb'] = round(memory_get_usage(true) / 1048576, 2);
            }

            // Channel-based file routing
            $filename = match ($channel) {
                self::CHANNEL_SECURITY => 'security.log',
                self::CHANNEL_QUEUE    => 'queue.log',
                self::CHANNEL_ERROR    => 'error.log',
                default                => 'app.log',
            };

            $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            @file_put_contents(self::getDir() . '/' . $filename, $json, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            error_log('[LOGGER_FAILURE] ' . $e->getMessage());
        }
    }

    // ─── Convenience Methods ─────────────────────────────

    public static function debug(string $msg, array $ctx = []): void {
        self::log(self::DEBUG, $msg, $ctx);
    }

    public static function info(string $msg, array $ctx = []): void {
        self::log(self::INFO, $msg, $ctx);
    }

    public static function warning(string $msg, array $ctx = []): void {
        self::log(self::WARNING, $msg, $ctx);
    }

    public static function error(string $msg, array $ctx = []): void {
        self::log(self::ERROR, $msg, $ctx);
    }

    public static function critical(string $msg, array $ctx = []): void {
        self::log(self::CRITICAL, $msg, $ctx);
    }

    // ─── Specialized Channels ────────────────────────────

    /**
     * Log a security event (always logged regardless of level).
     */
    public static function security(string $msg, array $ctx = []): void {
        self::log(self::WARNING, $msg, $ctx, self::CHANNEL_SECURITY);
    }

    /**
     * Log a queue/worker event.
     */
    public static function queue(string $msg, array $ctx = []): void {
        self::log(self::INFO, $msg, $ctx, self::CHANNEL_QUEUE);
    }

    /**
     * Log a performance metric.
     */
    public static function metric(string $name, float $value, array $tags = []): void {
        self::log(self::INFO, "metric:{$name}", array_merge(['value' => $value], $tags));
    }

    /**
     * Log an audit event (tenant operation tracking).
     */
    public static function audit(string $action, string $entity = '', $entityId = null, array $ctx = []): void {
        self::log(self::INFO, "audit:{$action}", array_merge([
            'entity' => $entity,
            'entity_id' => $entityId,
        ], $ctx));
    }
}
