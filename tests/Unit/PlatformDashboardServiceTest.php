<?php
/**
 * Unit Tests - PlatformDashboardService
 */

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/PlatformDashboardService.php';

if (!class_exists('PlatformDashboardFakeStatement')) {
    class PlatformDashboardFakeStatement {
        /** @var mixed */
        private $value;

        /** @param mixed $value */
        public function __construct($value) {
            $this->value = $value;
        }

        /** @return mixed */
        public function fetchColumn() {
            return $this->value;
        }

        /** @return mixed */
        public function fetch() {
            return $this->value;
        }

        /** @return mixed */
        public function fetchAll($mode = null) {
            return $this->value;
        }
    }
}

if (!class_exists('PlatformDashboardFakeDatabase')) {
    class PlatformDashboardFakeDatabase {
        /** @var array<int, mixed> */
        public array $queue = [];

        public function query($sql, $params = []) {
            $value = array_shift($this->queue);
            if ($value instanceof Throwable) {
                throw $value;
            }
            return new PlatformDashboardFakeStatement($value);
        }
    }
}

class PlatformDashboardServiceTest extends BaseTestCase {
    public function testBuildViewDataReturnsAggregatedMetrics(): void {
        $db = new PlatformDashboardFakeDatabase();
        $db->queue = [
            12,
            8,
            2,
            2,
            47,
            154320.50,
            9600.00,
            ['pending' => 4, 'failed' => 1],
            1,
            7,
            21000.00,
            [
                ['name' => 'Enterprise', 'subscribers' => 5],
                ['name' => 'Professional', 'subscribers' => 2],
            ],
            ['total_codes' => 3, 'total_usage' => 19, 'total_discount' => 4500.00],
            [
                ['referral_status' => 'pending', 'cnt' => 2],
                ['referral_status' => 'successful', 'cnt' => 5],
            ],
            [['id' => 91, 'company_name' => 'Acme', 'plan_name' => 'Enterprise']],
            [['id' => 77, 'company_name' => 'Beta']],
            [['id' => 31, 'company_name' => 'Acme', 'plan_name' => 'Enterprise']],
        ];

        $service = new PlatformDashboardService($db);
        $data = $service->buildViewData();

        $this->assertSame('Platform Dashboard', $data['pageTitle']);
        $this->assertSame(12, $data['metrics']['totalTenants']);
        $this->assertSame(9600.00, $data['metrics']['mrr']);
        $this->assertSame(7, $data['metrics']['activeSubscriptions']);
        $this->assertSame(4, $data['queue']['pending']);
        $this->assertSame(1, $data['queue']['failed']);
        $this->assertSame(5, $data['referralStats']['successful']);
        $this->assertCount(1, $data['recentPayments']);
        $this->assertCount(1, $data['recentFailedPayments']);
        $this->assertCount(1, $data['recentLifecycle']);
        $this->assertStringEndsWith(' ms', $data['sysHealth']['latency']);
    }

    public function testBuildViewDataFallsBackWhenBillingMetricsUnavailable(): void {
        $db = new PlatformDashboardFakeDatabase();
        $db->queue = [
            4,
            3,
            1,
            0,
            11,
            2500.00,
            800.00,
            new RuntimeException('jobs unavailable'),
            1,
            new RuntimeException('billing unavailable'),
        ];

        $service = new PlatformDashboardService($db);
        $data = $service->buildViewData();

        $this->assertSame(0, $data['queue']['pending']);
        $this->assertSame(0, $data['queue']['failed']);
        $this->assertSame(0, $data['metrics']['activeSubscriptions']);
        $this->assertSame(0.0, $data['metrics']['totalRevenue']);
        $this->assertSame([], $data['planWiseSubscribers']);
        $this->assertSame(['pending' => 0, 'successful' => 0, 'rewarded' => 0], $data['referralStats']);
    }
}
