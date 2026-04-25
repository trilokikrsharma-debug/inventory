<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/TwoFactorService.php';
require_once dirname(__DIR__, 2) . '/services/TwoFactorWorkflowService.php';

class TwoFactorWorkflowServiceTest extends BaseTestCase {
    public function testSanitizeSessionUserRemovesSensitiveFields(): void {
        $service = new TwoFactorWorkflowService(new TwoFactorWorkflowFakeDb());

        $sanitized = $service->sanitizeSessionUser([
            'id' => 7,
            'password' => 'secret',
            'twofa_secret' => 'abc',
            'twofa_recovery_codes' => '[]',
            'company_status' => 'active',
            'company_name' => 'Tenant',
            'is_super_admin' => 0,
        ], false);

        $this->assertSame(7, $sanitized['id']);
        $this->assertArrayNotHasKey('password', $sanitized);
        $this->assertArrayNotHasKey('twofa_secret', $sanitized);
        $this->assertArrayNotHasKey('twofa_recovery_codes', $sanitized);
        $this->assertArrayNotHasKey('company_status', $sanitized);
        $this->assertFalse((bool)$sanitized['is_super_admin']);
    }

    public function testLoadLoginContextReturnsActiveTenantContext(): void {
        $db = new TwoFactorWorkflowFakeDb([
            [
                'id' => 12,
                'company_id' => 44,
                'role_id' => 5,
                'is_super_admin' => 0,
                'username' => 'owner',
            ],
            ['is_super_admin' => 0],
            [
                'id' => 44,
                'name' => 'Tenant Co',
                'status' => 'active',
            ],
        ]);
        $service = new TwoFactorWorkflowService($db);

        $context = $service->loadLoginContext(12);

        $this->assertSame(44, $context['company_id'] ?? null);
        $this->assertSame('Tenant Co', $context['company']['name'] ?? null);
        $this->assertFalse($context['is_super_admin'] ?? true);
    }

    public function testLoadLoginContextReturnsNullForInactiveTenant(): void {
        $db = new TwoFactorWorkflowFakeDb([
            [
                'id' => 12,
                'company_id' => 44,
                'role_id' => 5,
                'is_super_admin' => 0,
                'username' => 'owner',
            ],
            ['is_super_admin' => 0],
            null,
        ]);
        $service = new TwoFactorWorkflowService($db);

        $this->assertNull($service->loadLoginContext(12));
    }
}

class TwoFactorWorkflowFakeDb {
    private array $queue;

    public function __construct(array $queue = []) {
        $this->queue = $queue;
    }

    public function query($sql, $params = []) {
        $value = array_shift($this->queue);
        return new TwoFactorWorkflowFakeResult($value);
    }
}

class TwoFactorWorkflowFakeResult {
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function fetch($mode = null) {
        return $this->value;
    }
}
