<?php
/**
 * Unit Tests - Phase 2 Tenant Policy
 */

require_once __DIR__ . '/../BaseTestCase.php';

if (!class_exists('Phase2FakeStatement')) {
    class Phase2FakeStatement {
        private array $rows;

        public function __construct(array $rows) {
            $this->rows = array_values($rows);
        }

        public function fetchAll(): array {
            return $this->rows;
        }

        public function fetch() {
            return $this->rows[0] ?? false;
        }

        public function fetchColumn() {
            if (empty($this->rows)) {
                return false;
            }

            $first = $this->rows[0];
            if (!is_array($first)) {
                return $first;
            }

            $value = reset($first);
            return $value === false ? false : $value;
        }

        public function rowCount(): int {
            return count($this->rows);
        }
    }
}

if (!class_exists('Phase2FakeDatabase')) {
    class Phase2FakeDatabase {
        /** @var array<int, array<string, mixed>> */
        public array $permissionRows = [];

        /** @var array<string, mixed>|null */
        public ?array $planRow = null;

        /** @var array<string, mixed>|null */
        public ?array $companyRow = null;

        public bool $denyCrossTenantPermissions = false;
        public ?int $expectedTenantCompanyId = null;
        public ?int $expectedRoleCompanyId = null;

        public function query($sql, $params = []) {
            $sql = (string)$sql;
            $params = is_array($params) ? array_values($params) : [$params];

            if (stripos($sql, 'FROM roles') !== false && stripos($sql, 'JOIN role_permissions') === false && stripos($sql, 'FROM permissions p') === false) {
                $roleId = (int)($params[0] ?? 0);
                if ($roleId > 0) {
                    return new Phase2FakeStatement([[
                        'id' => $roleId,
                        'company_id' => $this->expectedRoleCompanyId,
                        'is_super_admin' => false,
                    ]]);
                }

                return new Phase2FakeStatement([]);
            }

            if (stripos($sql, 'FROM permissions p') !== false && stripos($sql, 'JOIN role_permissions rp') !== false) {
                if ($this->denyCrossTenantPermissions && $this->expectedTenantCompanyId !== null && $this->expectedRoleCompanyId !== null && $this->expectedTenantCompanyId !== $this->expectedRoleCompanyId) {
                    return new Phase2FakeStatement([]);
                }

                return new Phase2FakeStatement($this->permissionRows);
            }

            if (stripos($sql, 'FROM saas_plans') !== false) {
                if ($this->planRow !== null) {
                    $planId = (int)($params[0] ?? 0);
                    if ($planId > 0 && $planId === (int)($this->planRow['id'] ?? 0)) {
                        return new Phase2FakeStatement([$this->planRow]);
                    }
                }

                return new Phase2FakeStatement([]);
            }

            if (stripos($sql, 'FROM companies') !== false && $this->companyRow !== null) {
                return new Phase2FakeStatement([$this->companyRow]);
            }

            return new Phase2FakeStatement([]);
        }

        public function getConnection() {
            return new class {
                public function lastInsertId() {
                    return 1;
                }
            };
        }

        public function lastInsertId() {
            return 1;
        }
    }
}

class Phase2TenantPolicyTest extends BaseTestCase {
    private ?object $originalDatabaseInstance = null;
    private string $cacheDir;
    private static ?string $sharedCacheDir = null;

    protected function setUp(): void {
        parent::setUp();

        if (self::$sharedCacheDir === null) {
            self::$sharedCacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'invenbill_phase2_cache_' . bin2hex(random_bytes(4));
        }

        $this->cacheDir = self::$sharedCacheDir;

        if (!defined('CACHE_PATH')) {
            define('CACHE_PATH', $this->cacheDir);
        }
        if (!defined('REDIS_ENABLED')) {
            define('REDIS_ENABLED', false);
        }
        if (!defined('SESSION_LIFETIME')) {
            define('SESSION_LIFETIME', 7200);
        }

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        $this->resetRuntimeState();
    }

    protected function tearDown(): void {
        $this->restoreDatabaseInstance();
        $this->resetRuntimeState();

        if (is_dir($this->cacheDir)) {
            foreach (glob($this->cacheDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function testPermissionLoaderDeniesCrossTenantRolePermissions(): void {
        $fakeDb = new Phase2FakeDatabase();
        $fakeDb->denyCrossTenantPermissions = true;
        $fakeDb->expectedTenantCompanyId = 10;
        $fakeDb->expectedRoleCompanyId = 20;
        $fakeDb->permissionRows = [
            ['name' => 'sales.view'],
            ['name' => 'products.view'],
        ];

        $this->swapDatabaseInstance($fakeDb);

        $_SESSION = [
            'user' => [
                'id' => 1001,
                'company_id' => 10,
                'role_id' => 7,
                'role' => 'staff',
                'is_super_admin' => false,
            ],
        ];

        session_id('phase2_rbac_' . uniqid('', true));

        $this->assertFalse(
            Session::hasPermission('sales.view'),
            'A role that belongs to another tenant must not leak permissions into the active tenant session.'
        );
    }

    public function testPermissionLoaderAllowsTenantOwnedRolePermissions(): void {
        $fakeDb = new Phase2FakeDatabase();
        $fakeDb->denyCrossTenantPermissions = true;
        $fakeDb->expectedTenantCompanyId = 10;
        $fakeDb->expectedRoleCompanyId = 10;
        $fakeDb->permissionRows = [
            ['name' => 'sales.view'],
            ['name' => 'products.view'],
        ];

        $this->swapDatabaseInstance($fakeDb);

        $_SESSION = [
            'user' => [
                'id' => 1002,
                'company_id' => 10,
                'role_id' => 7,
                'role' => 'staff',
                'is_super_admin' => false,
            ],
        ];

        session_id('phase2_rbac_' . uniqid('', true));

        $this->assertTrue(Session::hasPermission('sales.view'));
        $this->assertTrue(Session::hasPermission('products.view'));
    }

    public function testInactiveSubscriptionBlocksPremiumRouteAndAllowsBillingRecoveryRoute(): void {
        $blocked = $this->runIsolatedMiddlewareScript('reports', 'index', [
            'id' => 501,
            'company_id' => 42,
            'subscription_status' => 'inactive',
            'trial_ends_at' => '2026-03-01 00:00:00',
            'status' => 'active',
        ]);

        $this->assertStringContainsString('__RESULT__', $blocked);
        $this->assertStringNotContainsString('__NEXT__', $blocked);

        $blockedPayload = $this->extractIsolatedResult($blocked);
        $this->assertArrayHasKey('flash', $blockedPayload);
        $this->assertNotEmpty($blockedPayload['flash']['error'] ?? null);
        $blockedMessage = strtolower((string)($blockedPayload['flash']['error'] ?? ''));
        $this->assertTrue(
            str_contains($blockedMessage, 'inactive') || str_contains($blockedMessage, 'expired'),
            'Expected subscription guard message to mention inactive or expired state.'
        );

        $allowed = $this->runIsolatedMiddlewareScript('saas_billing', 'subscribe', [
            'id' => 501,
            'company_id' => 42,
            'subscription_status' => 'inactive',
            'trial_ends_at' => '2026-03-01 00:00:00',
            'status' => 'active',
        ]);

        $this->assertStringContainsString('__NEXT__', $allowed);
        $allowedPayload = $this->extractIsolatedResult($allowed);
        $this->assertEmpty($allowedPayload['flash'] ?? []);
    }

    public function testTenantCanUseReadsConfiguredMaxUserAndProductFeatureFlags(): void {
        Tenant::set(42, [
            'id' => 42,
            'company_id' => 42,
            'saas_plan_id' => 0,
            'plan' => 'starter',
            'max_users' => 5,
            'max_products' => 10,
            'subscription_status' => 'active',
            'status' => 'active',
        ]);

        $this->assertTrue(Tenant::canUse('max_users', 4), 'A tenant should remain within its user quota until the limit is reached.');
        $this->assertFalse(Tenant::canUse('max_users', 5), 'A tenant should be blocked once user usage reaches the quota.');
        $this->assertTrue(Tenant::canUse('max_products', 9), 'A tenant should remain within its product quota until the limit is reached.');
        $this->assertFalse(Tenant::canUse('max_products', 10), 'A tenant should be blocked once product usage reaches the quota.');
    }

    private function swapDatabaseInstance(object $instance): void {
        $property = new ReflectionProperty(Database::class, 'instance');
        $property->setAccessible(true);

        if ($this->originalDatabaseInstance === null) {
            $this->originalDatabaseInstance = $property->getValue();
        }

        $property->setValue(null, $instance);
    }

    private function restoreDatabaseInstance(): void {
        if ($this->originalDatabaseInstance === null) {
            return;
        }

        $property = new ReflectionProperty(Database::class, 'instance');
        $property->setAccessible(true);
        $property->setValue(null, $this->originalDatabaseInstance);
        $this->originalDatabaseInstance = null;
    }

    private function resetRuntimeState(): void {
        $_SESSION = [];
        Tenant::reset();
        Session::clearPermissionCache();
    }

    private function runIsolatedMiddlewareScript(string $page, string $action, array $company): string {
        $basePath = str_replace('\\', '/', BASE_PATH);
        $script = <<<'PHP'
    define('BASE_PATH', '__BASE_PATH__');
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

class IsolatedGuardStatement {
    private array $rows;

    public function __construct(array $rows = []) {
        $this->rows = array_values($rows);
    }

    public function fetchAll(): array {
        return $this->rows;
    }

    public function fetch() {
        return $this->rows[0] ?? false;
    }

    public function fetchColumn() {
        if (empty($this->rows)) {
            return false;
        }
        $first = $this->rows[0];
        if (!is_array($first)) {
            return $first;
        }
        $value = reset($first);
        return $value === false ? false : $value;
    }

    public function rowCount(): int {
        return count($this->rows);
    }
}

class IsolatedGuardDatabase {
    private array $company;

    public function __construct(array $company) {
        $this->company = $company;
    }

    public function query($sql, $params = []) {
        $sql = (string)$sql;

        if (stripos($sql, 'FROM companies') !== false) {
            return new IsolatedGuardStatement([$this->company]);
        }

        if (stripos($sql, 'FROM tenant_subscriptions') !== false) {
            return new IsolatedGuardStatement([]);
        }

        if (stripos($sql, 'UPDATE companies') !== false || stripos($sql, 'UPDATE tenant_subscriptions') !== false) {
            return new IsolatedGuardStatement([]);
        }

        return new IsolatedGuardStatement([]);
    }

    public function beginTransaction(): bool {
        return true;
    }

    public function commit(): bool {
        return true;
    }

    public function rollback(): bool {
        return true;
    }

    public function getConnection() {
        return new class {
            public function lastInsertId() {
                return 1;
            }
        };
    }

    public function lastInsertId() {
        return 1;
    }
}

$_SERVER['REQUEST_URI'] = '/index.php?page=__PAGE__&action=__ACTION__';
$dbProp = new ReflectionProperty(Database::class, 'instance');
$dbProp->setAccessible(true);
$dbProp->setValue(null, new IsolatedGuardDatabase(__COMPANY__));

$_SESSION = [];
$_SESSION['user'] = [
    'id' => 9001,
    'company_id' => 42,
    'role' => 'staff',
    'role_id' => 7,
    'is_super_admin' => 0,
];

Tenant::set(42, __COMPANY__);

$request = Request::create(
    [
        'page' => '__PAGE__',
        'action' => '__ACTION__',
    ],
    [],
    [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/index.php?page=__PAGE__&action=__ACTION__',
    ],
    []
);

register_shutdown_function(function (): void {
    echo "\n__RESULT__" . json_encode([
        'flash' => $_SESSION['flash'] ?? [],
        'user' => $_SESSION['user'] ?? null,
    ]);
});

$middleware = new SubscriptionGuardMiddleware();
$middleware->handle($request, function ($request) {
    echo '__NEXT__';
});
PHP;

        $script = str_replace(
            ['__BASE_PATH__', '__PAGE__', '__ACTION__', '__COMPANY__'],
            [
                $basePath,
                $page,
                $action,
                var_export($company, true),
            ],
            $script
        );

        $tempFile = tempnam(sys_get_temp_dir(), 'phase2_guard_');
        file_put_contents($tempFile, "<?php\n" . $script);

        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tempFile);
        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            @unlink($tempFile);
            $this->fail('Unable to launch isolated middleware process.');
        }

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        proc_close($process);

        $output = (string)($stdout . $stderr);
        @unlink($tempFile);

        return $output;
    }

    private function extractIsolatedResult(string $output): array {
        if (preg_match('/__RESULT__(\{.*\})/s', $output, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $this->fail('Failed to parse isolated middleware result. Raw output: ' . trim($output));
    }
}
