<?php
/**
 * Unit Tests - UserManagementService
 */

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Session.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/services/UserManagementService.php';

if (!class_exists('UserManagementFakeStatement')) {
    class UserManagementFakeStatement {
        /** @var mixed */
        private $value;

        /** @param mixed $value */
        public function __construct($value) {
            $this->value = $value;
        }

        /** @return mixed */
        public function fetch() {
            return $this->value;
        }

        /** @return mixed */
        public function fetchAll() {
            return $this->value;
        }
    }
}

if (!class_exists('UserManagementFakeDatabase')) {
    class UserManagementFakeDatabase {
        /** @var array<int, mixed> */
        public array $queue = [];

        public function query($sql, $params = []) {
            return new UserManagementFakeStatement(array_shift($this->queue));
        }
    }
}

if (!class_exists('UserManagementFakeUserModel')) {
    class UserManagementFakeUserModel {
        /** @var array<int, array<string, mixed>> */
        public array $users = [];

        public function find($id) {
            return $this->users[$id] ?? null;
        }
    }
}

class UserManagementServiceTest extends BaseTestCase {
    protected function tearDown(): void {
        Tenant::reset();
        $_SESSION = [];
        parent::tearDown();
    }

    public function testLoadAssignableRolesDeduplicatesGlobalAndTenantMatches(): void {
        Tenant::set(44, ['id' => 44, 'name' => 'Tenant 44']);
        $_SESSION['user'] = ['id' => 7, 'username' => 'manager', 'role' => 'admin', 'is_super_admin' => 0];

        $db = new UserManagementFakeDatabase();
        $db->queue[] = [
            ['id' => 12, 'name' => 'staff', 'display_name' => 'Staff', 'company_id' => 44, 'is_super_admin' => 0],
            ['id' => 4, 'name' => 'staff', 'display_name' => 'Staff', 'company_id' => null, 'is_super_admin' => 0],
            ['id' => 8, 'name' => 'cashier', 'display_name' => 'Cashier', 'company_id' => 44, 'is_super_admin' => 0],
        ];

        $service = new UserManagementService($db, new UserManagementFakeUserModel());
        $roles = $service->loadAssignableRoles();

        $this->assertCount(2, $roles);
        $this->assertSame(8, $roles[0]['id']);
        $this->assertSame(12, $roles[1]['id']);
    }

    public function testResolveAssignableRoleFallsBackWhenTenantUserTargetsSuperAdminRole(): void {
        Tenant::set(44, ['id' => 44, 'name' => 'Tenant 44']);
        $_SESSION['user'] = ['id' => 7, 'username' => 'manager', 'role' => 'admin', 'is_super_admin' => 0];

        $db = new UserManagementFakeDatabase();
        $db->queue[] = ['id' => 99, 'name' => 'platform_owner', 'display_name' => 'Platform Owner', 'company_id' => null, 'is_super_admin' => 1];
        $db->queue[] = ['id' => 3, 'name' => 'tenant_admin', 'display_name' => 'Tenant Admin', 'company_id' => null, 'is_super_admin' => 0];

        $service = new UserManagementService($db, new UserManagementFakeUserModel());
        $role = $service->resolveAssignableRole(99);

        $this->assertSame(3, $role['role_id']);
        $this->assertSame('Tenant Admin', $role['role_name']);
        $this->assertFalse($role['is_super_admin']);
    }

    public function testGuardManagedUserTargetBlocksSuperAdminPasswordResetForTenantAdmin(): void {
        $_SESSION['user'] = ['id' => 7, 'username' => 'manager', 'role' => 'admin', 'is_super_admin' => 0];

        $db = new UserManagementFakeDatabase();
        $db->queue[] = ['is_super_admin' => 1];

        $userModel = new UserManagementFakeUserModel();
        $userModel->users[15] = ['id' => 15, 'username' => 'owner', 'role_id' => 3, 'is_super_admin' => 0];

        $service = new UserManagementService($db, $userModel);
        $result = $service->guardManagedUserTarget(15, 'reset_password');

        $this->assertFalse($result['allowed']);
        $this->assertSame('You cannot reset the password of a super admin account.', $result['message']);
    }

    public function testGuardManagedUserTargetAllowsStandardDeleteForSuperAdminActor(): void {
        $_SESSION['user'] = ['id' => 1, 'username' => 'root', 'role' => 'admin', 'is_super_admin' => 1];

        $db = new UserManagementFakeDatabase();
        $userModel = new UserManagementFakeUserModel();
        $userModel->users[22] = ['id' => 22, 'username' => 'staff', 'role_id' => 9, 'is_super_admin' => 0];

        $service = new UserManagementService($db, $userModel);
        $result = $service->guardManagedUserTarget(22, 'delete');

        $this->assertTrue($result['allowed']);
        $this->assertSame('staff', $result['user']['username']);
    }
}
