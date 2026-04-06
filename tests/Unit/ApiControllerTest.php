<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Session.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/core/Controller.php';
require_once dirname(__DIR__, 2) . '/core/ApiAuth.php';
require_once dirname(__DIR__, 2) . '/controllers/ApiController.php';

class ApiControllerTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void {
        Tenant::reset();
        $_SESSION = [];
        parent::tearDown();
    }

    public function testRequireTenantApiManagementAccessRejectsNonTenantSession(): void {
        $_SESSION['user'] = [
            'id' => 5,
            'role' => 'admin',
            'company_id' => 0,
            'is_super_admin' => 0,
            'permissions' => ['settings.manage'],
        ];
        Tenant::reset();
        $controller = new TestApiController();

        try {
            $this->invokePrivate($controller, 'requireTenantApiManagementAccess');
            $this->fail('Expected redirect exception was not thrown.');
        } catch (TestRedirectException $e) {
            $this->assertSame('index.php?page=platform', $e->target);
            $this->assertSame('API token management is available only inside a tenant account.', $_SESSION['flash']['error'] ?? null);
        }
    }

    public function testGenerateRejectsGranularTokenWithoutScopes(): void {
        $_SESSION['user'] = [
            'id' => 5,
            'role' => 'admin',
            'company_id' => 44,
            'is_super_admin' => 0,
            'permissions' => ['settings.manage'],
        ];
        Tenant::set(44, ['id' => 44]);

        $controller = new TestApiController();
        $controller->setPostData([
            'name' => 'Warehouse Sync',
            'full_access' => '0',
            'scopes' => [],
            'expiry_days' => '30',
        ]);
        $controller->setRequestMethod('POST');

        try {
            $controller->generate();
            $this->fail('Expected redirect exception was not thrown.');
        } catch (TestRedirectException $e) {
            $this->assertSame('index.php?page=api', $e->target);
            $this->assertSame('Select at least one scope or keep full access enabled.', $_SESSION['flash']['error'] ?? null);
        }
    }

    public function testResolveExpiryTimestampAllowsSupportedDurationsOnly(): void {
        $controller = new TestApiController();

        $thirtyDays = $this->invokePrivate($controller, 'resolveExpiryTimestamp', ['30']);
        $invalid = $this->invokePrivate($controller, 'resolveExpiryTimestamp', ['14']);
        $never = $this->invokePrivate($controller, 'resolveExpiryTimestamp', ['never']);

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string)$thirtyDays);
        $this->assertNull($invalid);
        $this->assertNull($never);
    }

    private function invokePrivate(object $controller, string $method, array $args = []) {
        $ref = new ReflectionMethod(ApiController::class, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($controller, $args);
    }
}

class TestApiController extends ApiController {
    private array $postData = [];

    public function setPostData(array $data): void {
        $this->postData = $data;
    }

    public function setRequestMethod(string $method): void {
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
    }

    protected function validateCSRF() {
        return;
    }

    protected function requirePermission($permission) {
        return true;
    }

    protected function requireFeature($feature) {
        return true;
    }

    protected function post($key = null, $default = null) {
        if ($key === null) {
            return $this->postData;
        }
        return $this->postData[$key] ?? $default;
    }

    protected function redirect($url) {
        throw new TestRedirectException($url);
    }
}

class TestRedirectException extends RuntimeException {
    public string $target;

    public function __construct(string $target) {
        parent::__construct('Redirected to ' . $target);
        $this->target = $target;
    }
}
