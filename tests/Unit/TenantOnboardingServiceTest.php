<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/SaaSBillingHelper.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';
require_once dirname(__DIR__, 2) . '/models/Referral.php';
require_once dirname(__DIR__, 2) . '/services/TenantOnboardingService.php';

if (!class_exists('TenantOnboardingFakeStatement')) {
    class TenantOnboardingFakeStatement {
        private $value;
        public function __construct($value) { $this->value = $value; }
        public function fetch() { return $this->value; }
        public function fetchAll() { return $this->value; }
        public function fetchColumn() { return $this->value; }
    }
}

if (!class_exists('TenantOnboardingFakeDb')) {
    class TenantOnboardingFakeDb {
        public array $queue = [];
        public array $lastInsertIds = [];
        public array $queries = [];
        public int $beginCount = 0;
        public int $commitCount = 0;
        public int $rollbackCount = 0;
        public function beginTransaction(): void { $this->beginCount++; }
        public function commit(): void { $this->commitCount++; }
        public function rollback(): void { $this->rollbackCount++; }
        public function query($sql, $params = []) {
            $this->queries[] = ['sql' => $sql, 'params' => $params];
            $value = array_shift($this->queue);
            if ($value instanceof Throwable) {
                throw $value;
            }
            return new TenantOnboardingFakeStatement($value);
        }
        public function lastInsertId() {
            return array_shift($this->lastInsertIds) ?? 0;
        }
    }
}

if (!class_exists('TenantOnboardingFakeReferral')) {
    class TenantOnboardingFakeReferral extends Referral {
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

class TenantOnboardingServiceTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();
        if (!defined('PASSWORD_MIN_LENGTH')) {
            define('PASSWORD_MIN_LENGTH', 8);
        }
        if (!defined('PASSWORD_COMPLEXITY')) {
            define('PASSWORD_COMPLEXITY', true);
        }
    }

    public function testValidateRegistrationInputNormalizesAndRejectsReservedSubdomain(): void {
        $service = new TenantOnboardingService(new TenantOnboardingFakeDb(), new TenantOnboardingFakeReferral());
        $normalized = $service->validateRegistrationInput([
            'company_name' => ' <b>Acme</b> ',
            'subdomain' => 'acme-shop',
            'email' => 'Owner@Example.com ',
            'password' => 'Password1',
            'referral_code' => ' ref10 ',
        ]);

        $this->assertSame('Acme', $normalized['company_name']);
        $this->assertSame('acme-shop', $normalized['subdomain']);
        $this->assertSame('owner@example.com', $normalized['email']);
        $this->assertSame('REF10', $normalized['referral_code']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This subdomain is reserved. Please choose another one.');
        $service->validateRegistrationInput([
            'company_name' => 'Acme',
            'subdomain' => 'admin',
            'email' => 'owner@example.com',
            'password' => 'Password1',
        ]);
    }

    public function testEnsureAvailabilityRejectsDuplicateEmail(): void {
        $db = new TenantOnboardingFakeDb();
        $db->queue = [null, ['id' => 55]];
        $service = new TenantOnboardingService($db, new TenantOnboardingFakeReferral());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This email is already registered.');

        $service->ensureAvailability([
            'subdomain' => 'acme-shop',
            'email' => 'owner@example.com',
        ]);
    }

    public function testRegisterTenantCreatesTenantOwnerAndUnits(): void {
        $db = new TenantOnboardingFakeDb();
        $db->queue = [
            ['id' => 1, 'slug' => 'starter', 'name' => 'Starter', 'max_users' => 3, 'max_products' => 500],
            null,
            [['id' => 21], ['id' => 22]],
            0,
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
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        ];
        $db->lastInsertIds = [101, 9, 501];
        $referral = new TenantOnboardingFakeReferral();
        $service = new TenantOnboardingService($db, $referral);

        $result = $service->registerTenant([
            'company_name' => 'Acme Co',
            'subdomain' => 'acme-co',
            'email' => 'owner@example.com',
            'password' => 'Password1',
            'referral_code' => '',
        ]);

        $this->assertSame(101, $result['tenant_id']);
        $this->assertSame(501, $result['user_id']);
        $this->assertSame('owner', $result['username']);
        $this->assertSame([101], $referral->ensureCalls);
        $this->assertCount(0, $referral->assignCalls);
        $this->assertSame(1, $db->beginCount);
        $this->assertSame(1, $db->commitCount);
        $this->assertSame(0, $db->rollbackCount);
    }
}
