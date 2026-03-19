<?php
/**
 * Enterprise Rate Limiter — Per-Tenant + Per-IP
 * 
 * Sliding window rate limiting with Redis support and file-based fallback.
 * Designed for SaaS multi-tenant workloads.
 * 
 * Strategies:
 *   - Per-IP: Prevents abuse from single source
 *   - Per-Tenant: Prevents one tenant from starving others
 *   - Per-Endpoint: Fine-grained API rate control
 * 
 * Usage:
 *   // In middleware or front controller:
 *   if (!RateLimiter::attempt('api:' . $tenantId, 100, 60)) {
 *       http_response_code(429);
 *       echo json_encode(['error' => 'Rate limit exceeded']);
 *       exit;
 *   }
 * 
 *   // Check remaining:
 *   $remaining = RateLimiter::remaining('api:' . $tenantId, 100, 60);
 */
class RateLimiter {

    private static $driver = null; // 'redis' or 'file'
    private static $redis = null;

    /**
     * Detect available driver (Redis preferred, file fallback)
     */
    private static function getDriver(): string {
        if (self::$driver !== null) return self::$driver;
        
        // Try Redis first
        if (extension_loaded('redis') && getenv('REDIS_HOST')) {
            try {
                self::$redis = new \Redis();
                self::$redis->connect(
                    getenv('REDIS_HOST') ?: '127.0.0.1',
                    (int)(getenv('REDIS_PORT') ?: 6379),
                    1.0 // 1s timeout
                );
                $pass = getenv('REDIS_PASSWORD');
                if ($pass === false || trim((string)$pass) === '') {
                    $pass = getenv('REDIS_PASS'); // backward compatibility alias
                }
                if ($pass !== false && trim((string)$pass) !== '') {
                    self::$redis->auth((string)$pass);
                }
                self::$driver = 'redis';
                return 'redis';
            } catch (\Exception $e) {
                error_log('[RATE_LIMITER] Redis unavailable, falling back to file: ' . $e->getMessage());
            }
        }
        
        self::$driver = 'file';
        return 'file';
    }

    /**
     * Attempt a rate-limited action. Returns true if allowed, false if denied.
     * 
     * @param string $key     Unique identifier (e.g., "api:{tenant_id}" or "login:{ip}")
     * @param int    $maxHits Maximum number of attempts allowed in the window
     * @param int    $windowSeconds  Time window in seconds
     * @return bool  true if attempt is allowed
     */
    public static function attempt(string $key, int $maxHits, int $windowSeconds): bool {
        $key = 'rl:' . preg_replace('/[^a-zA-Z0-9_:\-]/', '_', $key);
        
        if (self::getDriver() === 'redis') {
            return self::redisAttempt($key, $maxHits, $windowSeconds);
        }
        return self::fileAttempt($key, $maxHits, $windowSeconds);
    }

    /**
     * Get remaining attempts
     */
    public static function remaining(string $key, int $maxHits, int $windowSeconds): int {
        $key = 'rl:' . preg_replace('/[^a-zA-Z0-9_:\-]/', '_', $key);
        
        if (self::getDriver() === 'redis') {
            $current = (int)self::$redis->get($key);
            return max(0, $maxHits - $current);
        }
        return max(0, $maxHits - self::fileGetCount($key, $windowSeconds));
    }

    /**
     * Add rate limit headers to response
     */
    public static function headers(string $key, int $maxHits, int $windowSeconds): void {
        $remaining = self::remaining($key, $maxHits, $windowSeconds);
        header("X-RateLimit-Limit: {$maxHits}");
        header("X-RateLimit-Remaining: {$remaining}");
        header("X-RateLimit-Reset: " . (time() + $windowSeconds));
    }

    // ─── Redis Implementation (atomic, O(1)) ───

    private static function redisAttempt(string $key, int $maxHits, int $windowSeconds): bool {
        $current = self::$redis->incr($key);
        if ($current === 1) {
            self::$redis->expire($key, $windowSeconds);
        }
        return $current <= $maxHits;
    }

    // ─── File-Based Fallback ───

    private static function getFilePath(string $key): string {
        $dir = defined('BASE_PATH') ? BASE_PATH . '/cache/rate_limits' : __DIR__ . '/../cache/rate_limits';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        return $dir . '/' . md5($key) . '.json';
    }

    private static function fileAttempt(string $key, int $maxHits, int $windowSeconds): bool {
        $file = self::getFilePath($key);
        $now = time();
        $data = ['hits' => 0, 'window_start' => $now];

        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            if ($raw) {
                $data = json_decode($raw, true) ?: $data;
            }
        }

        // Reset window if expired
        if ($now - ($data['window_start'] ?? 0) >= $windowSeconds) {
            $data = ['hits' => 0, 'window_start' => $now];
        }

        $data['hits']++;
        @file_put_contents($file, json_encode($data), LOCK_EX);

        return $data['hits'] <= $maxHits;
    }

    private static function fileGetCount(string $key, int $windowSeconds): int {
        $file = self::getFilePath($key);
        if (!file_exists($file)) return 0;
        $data = json_decode(@file_get_contents($file), true);
        if (!$data) return 0;
        if (time() - ($data['window_start'] ?? 0) >= $windowSeconds) return 0;
        return $data['hits'] ?? 0;
    }
}
