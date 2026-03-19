<?php
/**
 * Cache — Dual-Driver Cache System (Redis + File Fallback)
 * 
 * Automatically uses Redis when available and enabled.
 * Falls back to file-based cache with atomic writes and stampede protection.
 * 
 * Usage:
 *   Cache::set('key', $value, 300);          // 5 minutes
 *   $val = Cache::get('key');
 *   $val = Cache::remember('key', 300, fn() => expensive_query());
 *   Cache::delete('key');
 *   Cache::flush();                           // Clear all
 *   Cache::tags(['dashboard'])->flush();      // Clear by tag (Redis only)
 */
class Cache {
    private static ?string $cacheDir = null;
    private static ?\Redis $redis = null;
    private static bool $redisChecked = false;

    // ─── Driver Detection ────────────────────────────────────

    /**
     * Get a Redis connection if available and configured.
     */
    private static function redis(): ?\Redis {
        if (self::$redisChecked) {
            return self::$redis;
        }
        self::$redisChecked = true;

        if (!defined('REDIS_ENABLED') || !REDIS_ENABLED) {
            return null;
        }
        if (!extension_loaded('redis')) {
            return null;
        }

        try {
            $r = new \Redis();
            $connected = $r->connect(
                defined('REDIS_HOST') ? REDIS_HOST : '127.0.0.1',
                defined('REDIS_PORT') ? REDIS_PORT : 6379,
                2.0 // 2 second timeout
            );
            if (!$connected) return null;

            $password = defined('REDIS_PASSWORD') ? REDIS_PASSWORD : null;
            if ($password) $r->auth($password);

            $db = defined('REDIS_DB') ? REDIS_DB : 0;
            if ($db > 0) $r->select($db);

            $prefix = defined('REDIS_PREFIX') ? REDIS_PREFIX : 'invenbill:';
            $r->setOption(\Redis::OPT_PREFIX, $prefix);
            $r->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

            self::$redis = $r;
            return self::$redis;
        } catch (\Exception $e) {
            error_log('[CACHE] Redis connection failed: ' . $e->getMessage());
            return null;
        }
    }

    private static function useRedis(): bool {
        return self::redis() !== null;
    }

    // ─── File Cache Helpers ──────────────────────────────────

    private static function getDir(): string {
        if (self::$cacheDir === null) {
            self::$cacheDir = defined('CACHE_PATH') ? CACHE_PATH : (defined('BASE_PATH') ? BASE_PATH . '/cache' : __DIR__ . '/../cache');
        }
        // Ensure directory exists even if it was deleted after initialization.
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0755, true);
        }
        return self::$cacheDir;
    }

    private static function getFilePath(string $key): string {
        $key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        return self::getDir() . '/' . $key . '.cache';
    }

    // ─── Public API ──────────────────────────────────────────

    /**
     * Get a cached value.
     */
    public static function get(string $key): mixed {
        if (self::useRedis()) {
            $val = self::redis()->get($key);
            return $val === false ? null : $val;
        }

        // File fallback
        self::gc();
        $file = self::getFilePath($key);
        if (!file_exists($file)) return null;

        $data = @file_get_contents($file);
        if ($data === false) return null;

        $payload = @unserialize($data, ['allowed_classes' => false]);
        if (!is_array($payload) || !isset($payload['ttl'], $payload['value'])) {
            self::delete($key);
            return null;
        }

        if ($payload['ttl'] > 0 && time() > $payload['ttl']) {
            self::delete($key);
            return null;
        }

        return $payload['value'];
    }

    /**
     * Set a cached value.
     * 
     * @param string $key    Cache key
     * @param mixed  $value  Value to cache
     * @param int    $ttl    Time to live in seconds (0 = forever)
     */
    public static function set(string $key, mixed $value, int $ttl = 60): bool {
        if (self::useRedis()) {
            try {
                if ($ttl > 0) {
                    return self::redis()->setex($key, $ttl, $value);
                }
                return self::redis()->set($key, $value);
            } catch (\Exception $e) {
                error_log('[CACHE] Redis set failed: ' . $e->getMessage());
                return false;
            }
        }

        // File fallback
        try {
            $file = self::getFilePath($key);
            $payload = [
                'ttl' => $ttl > 0 ? time() + $ttl : 0,
                'value' => $value,
            ];
            $tempFile = $file . '.tmp_' . uniqid('', true);
            if (@file_put_contents($tempFile, serialize($payload)) !== false) {
                @rename($tempFile, $file);
                if (file_exists($tempFile)) @unlink($tempFile);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            error_log('[CACHE] Set failed for ' . $key . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a cached value.
     */
    public static function delete(string $key): void {
        if (self::useRedis()) {
            try { self::redis()->del($key); } catch (\Exception $e) {}
            return;
        }

        try {
            $file = self::getFilePath($key);
            if (file_exists($file)) @unlink($file);
        } catch (\Exception $e) {}
    }

    /**
     * Get or compute and cache a value (with stampede protection).
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed {
        // Try cache first
        try {
            $value = self::get($key);
            if ($value !== null) return $value;
        } catch (\Exception $e) {}

        if (self::useRedis()) {
            // Redis: use SETNX-based lock for stampede protection
            $lockKey = $key . ':lock';
            $gotLock = self::redis()->set($lockKey, 1, ['NX', 'EX' => 10]);

            if (!$gotLock) {
                // Another process is building the cache — wait briefly
                usleep(100000); // 100ms
                $value = self::get($key);
                if ($value !== null) return $value;
            }

            $value = $callback();
            if ($value !== false && $value !== null) {
                self::set($key, $value, $ttl);
            }

            if ($gotLock) {
                try { self::redis()->del($lockKey); } catch (\Exception $e) {}
            }

            return $value;
        }

        // File fallback: lock-based stampede protection
        $lockFile = self::getFilePath($key . '_lock');
        $fp = @fopen($lockFile, 'w+');
        $gotLock = false;

        if ($fp) {
            $timeout = 2.0;
            $start = microtime(true);
            while (microtime(true) - $start < $timeout) {
                if (@flock($fp, LOCK_EX | LOCK_NB)) {
                    $gotLock = true;
                    break;
                }
                usleep(50000);
            }

            if ($gotLock) {
                $value = self::get($key);
                if ($value !== null) {
                    @flock($fp, LOCK_UN);
                    @fclose($fp);
                    return $value;
                }
            } else {
                @fclose($fp);
                $fp = null;
            }
        }

        $value = $callback();
        if ($value !== false && $value !== null && ($gotLock || !$fp)) {
            self::set($key, $value, $ttl);
        }

        if ($gotLock && $fp) {
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }

        return $value;
    }

    /**
     * Flush all cache entries.
     */
    public static function flush(): void {
        if (self::useRedis()) {
            try { self::redis()->flushDB(); } catch (\Exception $e) {}
            return;
        }

        try {
            $dir = self::getDir();
            $files = glob($dir . '/*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
        } catch (\Exception $e) {}
    }

    /**
     * Flush cache keys matching a prefix pattern.
     */
    public static function flushPrefix(string $prefix): void {
        if (self::useRedis()) {
            try {
                $keys = self::redis()->keys($prefix . '*');
                if (!empty($keys)) {
                    // Remove the global prefix that Redis adds automatically
                    $globalPrefix = defined('REDIS_PREFIX') ? REDIS_PREFIX : 'invenbill:';
                    $cleaned = array_map(fn($k) => str_replace($globalPrefix, '', $k), $keys);
                    self::redis()->del(...$cleaned);
                }
            } catch (\Exception $e) {}
            return;
        }

        try {
            $dir = self::getDir();
            $safePrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $prefix);
            $files = glob($dir . '/' . $safePrefix . '*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
        } catch (\Exception $e) {}
    }

    /**
     * Check cache driver info (for health checks).
     */
    public static function driver(): string {
        return self::useRedis() ? 'redis' : 'file';
    }

    /**
     * Lightweight garbage collection (file cache only, ~1% of reads).
     */
    private static function gc(): void {
        if (self::useRedis()) return; // Redis has native TTL
        if (mt_rand(1, 100) !== 1) return;

        try {
            $dir = self::getDir();
            if (!is_dir($dir)) return;
            $now = time();
            $count = 0;

            foreach (new \DirectoryIterator($dir) as $f) {
                if (++$count > 500) break;
                if (!$f->isFile()) continue;

                $file = $f->getPathname();
                $name = $f->getFilename();

                // Clean orphaned temp files
                if (strpos($name, '.tmp_') !== false && $now - $f->getMTime() > 300) {
                    @unlink($file);
                    continue;
                }

                if ($f->getExtension() !== 'cache') continue;

                // Clean stale lock files
                if (strpos($file, '_lock.cache') !== false && $now - $f->getMTime() > 120) {
                    @unlink($file);
                    continue;
                }

                // Delete expired cache files
                $data = @file_get_contents($file);
                if ($data) {
                    $payload = @unserialize($data, ['allowed_classes' => false]);
                    if (is_array($payload) && isset($payload['ttl']) && $payload['ttl'] > 0 && $now > $payload['ttl']) {
                        @unlink($file);
                    }
                } elseif ($now - $f->getMTime() > 86400) {
                    @unlink($file);
                }
            }
        } catch (\Exception $e) {}
    }

    /**
     * Testing hook: reset static cache driver state between tests.
     */
    public static function resetForTests(?string $cacheDir = null): void {
        self::$redis = null;
        self::$redisChecked = false;
        self::$cacheDir = $cacheDir;
        if ($cacheDir !== null && !is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
    }
}
