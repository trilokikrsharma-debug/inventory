<?php
/**
 * Unit Tests - AuthController
 */

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/services/AuthRateLimitService.php';

class AuthControllerTest extends BaseTestCase {
    protected function tearDown(): void {
        Tenant::reset();
        parent::tearDown();
    }

    public function testGetRateLimitFileSeparatesTenantContexts(): void {
        $service = new AuthRateLimitService();

        Tenant::set(10, ['id' => 10]);
        $tenantTenPath = $service->getFilePath('127.0.0.1', 'Alice');

        Tenant::set(20, ['id' => 20]);
        $tenantTwentyPath = $service->getFilePath('127.0.0.1', 'Alice');

        $this->assertNotSame($tenantTenPath, $tenantTwentyPath);
        $this->assertStringContainsString('/cache/ratelimit_', str_replace('\\', '/', $tenantTenPath));
        $this->assertSame(dirname($tenantTenPath), dirname($tenantTwentyPath));
        $this->assertDirectoryExists(dirname($tenantTenPath));
    }

    public function testRateLimitRoundTripPersistsAndClearsRecord(): void {
        $service = new AuthRateLimitService();

        Tenant::set(44, ['id' => 44]);
        $username = 'tester@example.com';
        $ip = '203.0.113.9';
        $payload = [
            'attempts' => 3,
            'lockout_until' => time() + 60,
            'last_attempt' => time(),
        ];

        $filePath = $service->getFilePath($ip, $username);
        @unlink($filePath);

        $service->put($ip, $username, $payload);
        $stored = $service->get($ip, $username);

        $this->assertSame($payload['attempts'], $stored['attempts']);
        $this->assertSame($payload['lockout_until'], $stored['lockout_until']);
        $this->assertFileExists($filePath);

        $service->clear($ip, $username);
        $cleared = $service->get($ip, $username);

        $this->assertSame(
            ['attempts' => 0, 'lockout_until' => 0, 'last_attempt' => 0],
            $cleared
        );
        $this->assertFileDoesNotExist($filePath);
    }
}
