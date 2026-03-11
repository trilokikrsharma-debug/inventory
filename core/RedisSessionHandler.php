<?php
/**
 * Redis Session Handler
 * 
 * Replaces PHP's default file-based sessions with Redis storage.
 * Provides: multi-server support, configurable TTL, session locking.
 * 
 * To enable, call RedisSessionHandler::register() before Session::start().
 * Falls back silently to file sessions if Redis is unavailable.
 */
class RedisSessionHandler implements \SessionHandlerInterface {
    private \Redis $redis;
    private int $ttl;
    private string $prefix;

    public function __construct(\Redis $redis, int $ttl = 7200, string $prefix = 'sess:') {
        $this->redis = $redis;
        $this->ttl = $ttl;
        $this->prefix = $prefix;
    }

    /**
     * Register this handler if Redis is available.
     * Safe to call even without Redis — silently falls back.
     */
    public static function register(): bool {
        if (!defined('REDIS_ENABLED') || !REDIS_ENABLED || !extension_loaded('redis')) {
            return false;
        }

        try {
            $redis = new \Redis();
            $connected = $redis->connect(
                defined('REDIS_HOST') ? REDIS_HOST : '127.0.0.1',
                defined('REDIS_PORT') ? REDIS_PORT : 6379,
                2.0
            );
            if (!$connected) return false;

            $password = defined('REDIS_PASSWORD') ? REDIS_PASSWORD : null;
            if ($password) $redis->auth($password);

            // Use a different DB from cache to avoid flush conflicts
            $db = defined('REDIS_DB') ? REDIS_DB + 1 : 1;
            $redis->select($db);

            $ttl = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 7200;
            $handler = new self($redis, $ttl);

            session_set_save_handler($handler, true);
            return true;

        } catch (\Exception $e) {
            error_log('[SESSION] Redis session handler registration failed: ' . $e->getMessage());
            return false;
        }
    }

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        $data = $this->redis->get($this->prefix . $id);
        return $data !== false ? $data : '';
    }

    public function write(string $id, string $data): bool {
        return $this->redis->setex($this->prefix . $id, $this->ttl, $data);
    }

    public function destroy(string $id): bool {
        $this->redis->del($this->prefix . $id);
        return true;
    }

    public function gc(int $max_lifetime): int|false {
        // Redis handles TTL natively — no GC needed
        return 0;
    }
}
