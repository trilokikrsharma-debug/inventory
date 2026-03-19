<?php
/**
 * Enhanced Health Check Endpoint — Production Monitoring
 * 
 * Provides deep system health visibility for load balancers,
 * monitoring tools (Prometheus, Datadog), and ops teams.
 * 
 * Endpoint: /index.php?page=health
 * Returns: JSON with component-level status
 */
class HealthController extends Controller {

    protected $allowedActions = ['index'];

    public function index() {
        $startTime = microtime(true);
        $publicMode = $this->isPublicHealthModeEnabled();
        $isPrivileged = Session::isLoggedIn() && Session::isSuperAdmin();

        if (!$publicMode && !$isPrivileged) {
            $this->requireSuperAdmin();
            return;
        }

        $payload = $isPrivileged
            ? $this->buildDetailedPayload($startTime)
            : $this->buildPublicPayload($startTime);

        http_response_code($payload['http_status']);
        header('Content-Type: application/json');
        header('Cache-Control: no-store');

        unset($payload['http_status']);
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function buildDetailedPayload(float $startTime): array {
        $checks = $this->collectChecks();
        $summary = $this->evaluateChecks($checks);

        return [
            'http_status'   => $summary['http_status'],
            'status'        => $summary['status'],
            'version'       => $this->appVersion(),
            'environment'   => $this->appEnvironment(),
            'timestamp'     => date('c'),
            'response_ms'   => round((microtime(true) - $startTime) * 1000, 2),
            'uptime_s'      => isset($_SERVER['REQUEST_TIME']) ? time() - (int)$_SERVER['REQUEST_TIME'] : null,
            'checks'        => $checks,
        ];
    }

    private function buildPublicPayload(float $startTime): array {
        $summary = $this->evaluateChecks($this->collectChecks());

        return [
            'http_status' => $summary['http_status'],
            'status'      => $summary['status'],
            'version'     => $this->appVersion(),
            'timestamp'   => date('c'),
            'response_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'uptime_s'    => isset($_SERVER['REQUEST_TIME']) ? time() - (int)$_SERVER['REQUEST_TIME'] : null,
        ];
    }

    private function collectChecks(): array {
        return [
            'database'    => $this->checkDatabase(),
            'cache'       => $this->checkCache(),
            'redis'       => $this->checkRedis(),
            'queue'       => $this->checkQueue(),
            'disk'        => $this->checkDisk(),
            'memory'      => $this->checkMemory(),
            'uploads'     => $this->checkUploads(),
            'logs'        => $this->checkLogs(),
            'php'         => $this->checkPhp(),
        ];
    }

    private function evaluateChecks(array $checks): array {
        $allHealthy = !array_filter($checks, fn($c) => $c['status'] === 'error');
        $hasWarnings = (bool)array_filter($checks, fn($c) => $c['status'] === 'warning');
        $overallStatus = $allHealthy ? ($hasWarnings ? 'degraded' : 'healthy') : 'unhealthy';

        return [
            'status'      => $overallStatus,
            'http_status' => $allHealthy ? 200 : 503,
        ];
    }

    private function isPublicHealthModeEnabled(): bool {
        $flag = defined('HEALTH_PUBLIC_MODE') ? HEALTH_PUBLIC_MODE : getenv('HEALTH_PUBLIC_MODE');
        if ($flag === false || $flag === null || $flag === '') {
            $flag = getenv('HEALTH_ALLOW_PUBLIC');
        }

        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }

    private function appEnvironment(): string {
        if (defined('APP_ENV')) {
            return (string)APP_ENV;
        }
        $env = getenv('APP_ENV');
        return $env !== false && $env !== '' ? $env : 'production';
    }

    private function appVersion(): string {
        if (defined('APP_VERSION')) {
            return (string)APP_VERSION;
        }
        return '2.0.0';
    }

    private function checkDatabase(): array {
        try {
            $start = microtime(true);
            $db = Database::getInstance();
            $result = $db->query("SELECT 1 AS ok")->fetch();
            $latency = round((microtime(true) - $start) * 1000, 2);
            
            // Check connection pool info
            $threads = $db->query("SHOW STATUS LIKE 'Threads_connected'")->fetch();
            $maxConn = $db->query("SHOW VARIABLES LIKE 'max_connections'")->fetch();
            
            $connUsage = ($threads && $maxConn) 
                ? round(($threads['Value'] / $maxConn['Value']) * 100, 1) 
                : null;

            return [
                'status'        => 'ok',
                'latency_ms'    => $latency,
                'connections'   => $threads ? (int)$threads['Value'] : null,
                'max_connections' => $maxConn ? (int)$maxConn['Value'] : null,
                'usage_pct'     => $connUsage,
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database unreachable: ' . $e->getMessage()];
        }
    }

    private function checkCache(): array {
        try {
            $cacheDir = defined('CACHE_PATH') ? CACHE_PATH : BASE_PATH . '/cache';
            $writable = is_writable($cacheDir);
            
            // Check Redis if available
            $redisStatus = 'not_configured';
            if (extension_loaded('redis') && getenv('REDIS_HOST')) {
                try {
                    $r = new \Redis();
                    $r->connect(getenv('REDIS_HOST'), (int)(getenv('REDIS_PORT') ?: 6379), 1.0);
                    $r->ping();
                    $redisStatus = 'connected';
                    $r->close();
                } catch (\Exception $e) {
                    $redisStatus = 'error: ' . $e->getMessage();
                }
            }

            return [
                'status'       => $writable ? 'ok' : 'warning',
                'file_cache'   => $writable ? 'writable' : 'not writable',
                'redis'        => $redisStatus,
            ];
        } catch (\Exception $e) {
            return ['status' => 'warning', 'message' => $e->getMessage()];
        }
    }

    private function checkDisk(): array {
        $path = defined('BASE_PATH') ? BASE_PATH : __DIR__;
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);
        
        if ($total === false || $free === false) {
            return ['status' => 'warning', 'message' => 'Unable to read disk space'];
        }

        $usedPct = round((1 - ($free / $total)) * 100, 1);
        $status = $usedPct > 90 ? 'error' : ($usedPct > 80 ? 'warning' : 'ok');

        return [
            'status'    => $status,
            'total_gb'  => round($total / 1073741824, 2),
            'free_gb'   => round($free / 1073741824, 2),
            'used_pct'  => $usedPct,
        ];
    }

    private function checkMemory(): array {
        $usage = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = ini_get('memory_limit');
        
        // Parse memory limit to bytes
        $limitBytes = $this->parseBytes($limit);
        $usagePct = $limitBytes > 0 ? round(($usage / $limitBytes) * 100, 1) : null;
        $status = ($usagePct !== null && $usagePct > 80) ? 'warning' : 'ok';
        
        return [
            'status'    => $status,
            'current_mb'=> round($usage / 1048576, 2),
            'peak_mb'   => round($peak / 1048576, 2),
            'limit'     => $limit,
            'usage_pct' => $usagePct,
        ];
    }

    private function checkUploads(): array {
        $uploadDir = defined('UPLOAD_PATH') ? UPLOAD_PATH : BASE_PATH . '/uploads';
        $writable = is_writable($uploadDir);
        
        return [
            'status'    => $writable ? 'ok' : 'error',
            'writable'  => $writable,
            'path'      => basename($uploadDir),
        ];
    }

    private function checkLogs(): array {
        $logDir = BASE_PATH . '/logs';
        $writable = is_writable($logDir);
        
        // Count log files and total size
        $files = glob($logDir . '/*.{log,json}', GLOB_BRACE);
        $totalSize = 0;
        foreach ($files ?: [] as $f) {
            $totalSize += filesize($f);
        }
        
        return [
            'status'     => $writable ? 'ok' : 'warning',
            'writable'   => $writable,
            'file_count' => count($files ?: []),
            'total_mb'   => round($totalSize / 1048576, 2),
        ];
    }

    private function checkPhp(): array {
        return [
            'status'     => 'ok',
            'version'    => PHP_VERSION,
            'sapi'       => PHP_SAPI,
            'extensions' => [
                'pdo'     => extension_loaded('pdo'),
                'gd'      => extension_loaded('gd'),
                'curl'    => extension_loaded('curl'),
                'mbstring'=> extension_loaded('mbstring'),
                'redis'   => extension_loaded('redis'),
                'opcache' => extension_loaded('Zend OPcache'),
            ],
            'opcache_enabled' => function_exists('opcache_get_status') ? (bool)@opcache_get_status() : false,
        ];
    }

    private function parseBytes(string $value): int {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $num = (int)$value;
        return match($unit) {
            'g' => $num * 1073741824,
            'm' => $num * 1048576,
            'k' => $num * 1024,
            default => $num,
        };
    }

    private function checkRedis(): array {
        if (!defined('REDIS_ENABLED') || !REDIS_ENABLED) {
            return ['status' => 'ok', 'driver' => 'disabled'];
        }
        if (!extension_loaded('redis')) {
            return ['status' => 'warning', 'message' => 'Redis extension not loaded'];
        }

        try {
            $r = new \Redis();
            $connected = $r->connect(
                defined('REDIS_HOST') ? REDIS_HOST : '127.0.0.1',
                defined('REDIS_PORT') ? REDIS_PORT : 6379,
                2.0
            );
            if (!$connected) {
                return ['status' => 'error', 'message' => 'Connection failed'];
            }

            $password = defined('REDIS_PASSWORD') ? REDIS_PASSWORD : null;
            if ($password) $r->auth($password);

            $pong = $r->ping();
            $info = $r->info('memory');
            $r->close();

            return [
                'status'    => 'ok',
                'ping'      => $pong === true || $pong === '+PONG' ? 'PONG' : $pong,
                'memory_mb' => isset($info['used_memory']) ? round($info['used_memory'] / 1048576, 2) : null,
                'driver'    => 'connected',
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Redis: ' . $e->getMessage()];
        }
    }

    private function checkQueue(): array {
        try {
            $db = Database::getInstance();

            // Check if jobs table exists
            $tables = $db->query("SHOW TABLES LIKE 'jobs'")->fetchAll();
            if (empty($tables)) {
                return ['status' => 'ok', 'message' => 'Queue table not created yet'];
            }

            $stats = $db->query(
                "SELECT status, COUNT(*) as cnt FROM `jobs` GROUP BY status"
            )->fetchAll(\PDO::FETCH_KEY_PAIR);

            $pending = (int)($stats['pending'] ?? 0);
            $processing = (int)($stats['processing'] ?? 0);
            $failed = (int)($stats['failed'] ?? 0);
            $completed = (int)($stats['completed'] ?? 0);

            // Check for stuck jobs (processing > 30 min)
            $stuck = $db->query(
                "SELECT COUNT(*) FROM `jobs` WHERE status = 'processing' AND started_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
            )->fetchColumn();

            $status = 'ok';
            if ($failed > 10) $status = 'warning';
            if ((int)$stuck > 0) $status = 'warning';

            return [
                'status'     => $status,
                'pending'    => $pending,
                'processing' => $processing,
                'completed'  => $completed,
                'failed'     => $failed,
                'stuck'      => (int)$stuck,
            ];
        } catch (\Exception $e) {
            return ['status' => 'warning', 'message' => 'Queue check failed: ' . $e->getMessage()];
        }
    }
}
