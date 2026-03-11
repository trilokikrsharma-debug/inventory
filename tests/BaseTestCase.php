<?php
/**
 * InvenBill Pro — Base Test Case
 * 
 * Provides common test utilities, database isolation,
 * and factory methods for all test classes.
 */

use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase {
    protected static bool $dbInitialized = false;

    protected function setUp(): void {
        parent::setUp();

        // Define core constants if not already defined
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__));
        }
        if (!defined('APP_URL')) {
            define('APP_URL', 'http://localhost/inventory');
        }
        if (!defined('APP_ENV')) {
            define('APP_ENV', 'testing');
        }
        if (!defined('CSRF_TOKEN_NAME')) {
            define('CSRF_TOKEN_NAME', '_csrf_token');
        }
    }

    /**
     * Create a mock Request object.
     */
    protected function makeRequest(
        string $method = 'GET',
        array $get = [],
        array $post = [],
        array $server = []
    ): Request {
        $defaults = [
            'REQUEST_METHOD' => $method,
            'HTTP_HOST' => 'localhost',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_URI' => '/index.php',
        ];
        return Request::create(
            array_merge($defaults, $server),
            $get,
            $post
        );
    }

    /**
     * Assert that a JSON string contains expected keys.
     */
    protected function assertJsonHasKeys(string $json, array $keys): void {
        $data = json_decode($json, true);
        $this->assertIsArray($data, 'Failed to decode JSON');
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $data, "JSON missing key: {$key}");
        }
    }

    /**
     * Assert array has all specified keys.
     */
    protected function assertArrayHasKeys(array $array, array $keys): void {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, "Array missing key: {$key}");
        }
    }
}
