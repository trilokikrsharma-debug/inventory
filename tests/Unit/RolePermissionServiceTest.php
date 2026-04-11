<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Session.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/services/RolePermissionService.php';

if (!class_exists('RolePermissionFakeStatement')) {
    class RolePermissionFakeStatement {
        /** @var mixed */
        private $value;
        /** @param mixed $value */
        public function __construct($value) { $this->value = $value; }
        /** @return mixed */
        public function fetchAll($mode = null) { return $this->value; }
    }
}

if (!class_exists('RolePermissionFakeDb')) {
    class RolePermissionFakeDb {
        public array $queue = [];
        public array $queries = [];
        public function query($sql, $params = []) {
            $this->queries[] = ['sql' => $sql, 'params' => $params];
            return new RolePermissionFakeStatement(array_shift($this->queue));
        }
    }
}

class RolePermissionServiceTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();
        if (!defined('CONFIG_PATH')) {
            define('CONFIG_PATH', BASE_PATH . '/config');
        }
    }

    protected function tearDown(): void {
        Tenant::reset();
        $_SESSION = [];
        parent::tearDown();
    }

    public function testGroupedPermissionsFiltersFeatureGatedPermissionsForTenant(): void {
        Tenant::set(44, ['id' => 44]);
        $_SESSION['user'] = ['id' => 7, 'company_id' => 44, 'is_super_admin' => 0];

        $db = new RolePermissionFakeDb();
        $db->queue[] = [
            ['id' => 1, 'module' => 'sales', 'name' => 'sales.view'],
            ['id' => 2, 'module' => 'reports', 'name' => 'reports.view'],
            ['id' => 3, 'module' => 'backup', 'name' => 'backup.manage'],
        ];

        $service = new RolePermissionService($db);
        $grouped = $service->groupedPermissions();

        $this->assertArrayHasKey('sales', $grouped);
        $this->assertArrayNotHasKey('backup', $grouped);
        $this->assertArrayHasKey('reports', $grouped);
    }

    public function testReplaceRolePermissionsDeletesExistingAndInsertsOnlyAllowedIds(): void {
        Tenant::set(44, ['id' => 44]);
        $_SESSION['user'] = ['id' => 7, 'company_id' => 44, 'is_super_admin' => 0];

        $db = new RolePermissionFakeDb();
        $db->queue = [
            [],
            [1, 2, 3],
            [
                ['id' => 1, 'name' => 'sales.view'],
                ['id' => 2, 'name' => 'reports.view'],
                ['id' => 3, 'name' => 'backup.manage'],
            ],
            [],
        ];

        $service = new RolePermissionService($db);
        $service->replaceRolePermissions(15, ['1', '2', '3']);

        $insertQueries = array_values(array_filter($db->queries, static function (array $query): bool {
            return str_contains($query['sql'], 'INSERT INTO role_permissions');
        }));

        $this->assertCount(2, $insertQueries);
        $this->assertSame([15, 1], $insertQueries[0]['params']);
        $this->assertSame([15, 2], $insertQueries[1]['params']);
    }
}
