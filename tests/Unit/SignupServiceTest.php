<?php
/**
 * Unit Tests - SignupService
 */

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/SaaSBillingHelper.php';
require_once dirname(__DIR__, 2) . '/models/Referral.php';
require_once dirname(__DIR__, 2) . '/services/SignupService.php';

if (!class_exists('SignupServiceFakeStatement')) {
    class SignupServiceFakeStatement {
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

        /** @return mixed */
        public function fetchColumn() {
            return $this->value;
        }
    }
}

if (!class_exists('SignupServiceFakeDb')) {
    class SignupServiceFakeDb {
        /** @var array<int, mixed> */
        public array $queue = [];
        /** @var array<int, int> */
        public array $lastInsertIds = [];
        /** @var array<int, array{sql:string, params:array}> */
        public array $queries = [];
        public int $beginCount = 0;
        public int $commitCount = 0;
        public int $rollbackCount = 0;

        public function beginTransaction(): void {
            $this->beginCount++;
        }

        public function commit(): void {
            $this->commitCount++;
        }

        public function rollback(): void {
            $this->rollbackCount++;
        }

        public function query($sql, $params = []) {
            $this->queries[] = ['sql' => $sql, 'params' => $params];
            $value = array_shift($this->queue);
            if ($value instanceof Throwable) {
                throw $value;
            }
            return new SignupServiceFakeStatement($value);
        }

        public function lastInsertId() {
            return array_shift($this->lastInsertIds) ?? 0;
        }
    }
}

if (!class_exists('SignupServiceFakeReferral')) {
    class SignupServiceFakeReferral extends Referral {
        public array $ensureCalls = [];
        public array $assignCalls = [];
        public array $assignResult = ['success' => true];

        public function __construct() {}

        public function ensureCompanyReferralCode(int $companyId): string {
            $this->ensureCalls[] = $companyId;
            return 'REF-' . $companyId;
        }

        public function assignReferralToCompany(int $companyId, string $referralCode): array {
            $this->assignCalls[] = ['company_id' => $companyId, 'referral_code' => $referralCode];
            return $this->assignResult;
        }
    }
}

class SignupServiceTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();

        if (!defined('APP_NAME')) {
            define('APP_NAME', 'InvenBill');
        }
    }

    public function testGenerateSlugAppendsReservedSuffixAndUniquenessCounter(): void {
        $db = new SignupServiceFakeDb();
        $db->queue = [0, 1, 0];
        $service = new SignupService($db, new SignupServiceFakeReferral());

        $slug = $service->generateSlug('Admin');
        $duplicate = $service->generateSlug('Acme Co');

        $this->assertSame('admin-account', $slug);
        $this->assertSame('acme-co-1', $duplicate);
    }

    public function testResolveSignupPlanReturnsFallbackWhenQueriesFail(): void {
        $db = new SignupServiceFakeDb();
        $db->queue = [new RuntimeException('missing'), new RuntimeException('missing-again')];
        $service = new SignupService($db, new SignupServiceFakeReferral());

        $plan = $service->resolveSignupPlan();

        $this->assertNull($plan['id']);
        $this->assertSame('starter', $plan['slug']);
        $this->assertSame(3, $plan['max_users']);
    }

    public function testRegisterTenantCreatesOwnerCompanyAndDefaultData(): void {
        $db = new SignupServiceFakeDb();
        $db->queue = [
            0,
            ['id' => 4, 'slug' => 'starter', 'name' => 'Starter', 'max_users' => 3, 'max_products' => 500, 'features' => null],
            null,
            null,
            ['id' => 9, 'name' => 'admin'],
            null,
            [['id' => 21], ['id' => 22]],
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            ['id' => 501, 'company_id' => 101, 'username' => 'owner', 'is_super_admin' => 0],
            ['id' => 101, 'name' => 'Acme Co', 'slug' => 'acme-co'],
        ];
        $db->lastInsertIds = [101, 9, 501];
        $referral = new SignupServiceFakeReferral();
        $service = new SignupService($db, $referral);

        $result = $service->registerTenant([
            'company_name' => 'Acme Co',
            'full_name' => 'Owner User',
            'email' => 'owner@example.com',
            'phone' => '9999999999',
            'username' => 'owner',
            'password' => 'Password1',
            'referral_code' => '',
        ]);

        $this->assertSame(101, $result['company_id']);
        $this->assertSame(501, $result['user_id']);
        $this->assertSame([101], $referral->ensureCalls);
        $this->assertCount(0, $referral->assignCalls);
        $this->assertSame(1, $db->beginCount);
        $this->assertSame(1, $db->commitCount);
        $this->assertSame(0, $db->rollbackCount);
    }
}
