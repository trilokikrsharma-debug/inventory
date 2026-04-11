<?php
/**
 * Persistent auth rate-limit storage.
 *
 * Keeps file-based login attempt tracking out of the controller layer while
 * preserving the tenant-aware keying used to avoid shared-IP collisions.
 */
class AuthRateLimitService {
    private const ATTEMPT_WINDOW = 600;

    public function get(string $ip, string $username): array {
        $default = ['attempts' => 0, 'lockout_until' => 0, 'last_attempt' => 0];
        $file = $this->getFilePath($ip, $username);

        if (!file_exists($file)) {
            return $default;
        }

        $data = @file_get_contents($file);
        if ($data === false) {
            return $default;
        }

        $parsed = @json_decode($data, true);
        if (!is_array($parsed)) {
            @unlink($file);
            return $default;
        }

        if (time() - ($parsed['last_attempt'] ?? 0) > self::ATTEMPT_WINDOW) {
            @unlink($file);
            return $default;
        }

        return array_merge($default, $parsed);
    }

    public function put(string $ip, string $username, array $data): void {
        $file = $this->getFilePath($ip, $username);
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }

    public function clear(string $ip, string $username): void {
        $file = $this->getFilePath($ip, $username);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public function getFilePath(string $ip, string $username): string {
        $dir = defined('BASE_PATH') ? BASE_PATH . '/cache' : __DIR__ . '/../cache';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $tenantId = class_exists('Tenant') ? (Tenant::id() ?? 0) : 0;
        $key = hash('sha256', $tenantId . '_' . $ip . '_' . strtolower(trim($username)));
        return $dir . '/ratelimit_' . $key . '.json';
    }
}
