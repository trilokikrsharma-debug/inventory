<?php
/**
 * Unit Tests — Cache
 */

require_once __DIR__ . '/../BaseTestCase.php';

class CacheTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();
        if (!defined('CACHE_PATH')) {
            define('CACHE_PATH', sys_get_temp_dir() . '/invenbill_test_cache');
        }
        if (!defined('REDIS_ENABLED')) {
            define('REDIS_ENABLED', false);
        }
        // Clear cache directory
        $dir = CACHE_PATH;
        if (is_dir($dir)) {
            array_map('unlink', glob($dir . '/*'));
        }
        Cache::resetForTests($dir);
    }

    protected function tearDown(): void {
        // Cleanup
        $dir = defined('CACHE_PATH') ? CACHE_PATH : sys_get_temp_dir() . '/invenbill_test_cache';
        if (is_dir($dir)) {
            array_map('unlink', glob($dir . '/*'));
            @rmdir($dir);
        }
        parent::tearDown();
    }

    public function testSetAndGet(): void {
        Cache::set('test_key', 'test_value', 60);
        $this->assertEquals('test_value', Cache::get('test_key'));
    }

    public function testGetReturnsNullForMissing(): void {
        $this->assertNull(Cache::get('nonexistent_key'));
    }

    public function testDelete(): void {
        Cache::set('delete_me', 'value', 60);
        $this->assertEquals('value', Cache::get('delete_me'));

        Cache::delete('delete_me');
        $this->assertNull(Cache::get('delete_me'));
    }

    public function testRememberCachesCallbackResult(): void {
        $callCount = 0;

        $result1 = Cache::remember('computed', 60, function () use (&$callCount) {
            $callCount++;
            return 'expensive_result';
        });

        $result2 = Cache::remember('computed', 60, function () use (&$callCount) {
            $callCount++;
            return 'should_not_run';
        });

        $this->assertEquals('expensive_result', $result1);
        $this->assertEquals('expensive_result', $result2);
        $this->assertEquals(1, $callCount, 'Callback should only run once');
    }

    public function testSetWithArrayValue(): void {
        $data = ['name' => 'Test', 'items' => [1, 2, 3]];
        Cache::set('array_test', $data, 60);

        $result = Cache::get('array_test');
        $this->assertEquals($data, $result);
    }

    public function testDriverReturnsFile(): void {
        $this->assertEquals('file', Cache::driver());
    }

    public function testFlushClearsAll(): void {
        Cache::set('key1', 'val1', 60);
        Cache::set('key2', 'val2', 60);

        Cache::flush();

        $this->assertNull(Cache::get('key1'));
        $this->assertNull(Cache::get('key2'));
    }
}
