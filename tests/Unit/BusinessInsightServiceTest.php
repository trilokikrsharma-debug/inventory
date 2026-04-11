<?php
/**
 * Unit Tests - BusinessInsightService
 */

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/services/BusinessInsightService.php';

if (!class_exists('BusinessInsightFakeStatement')) {
    class BusinessInsightFakeStatement {
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
    }
}

if (!class_exists('BusinessInsightFakeDatabase')) {
    class BusinessInsightFakeDatabase {
        /** @var array<int, mixed> */
        public array $queue = [];

        public function query($sql, $params = []) {
            return new BusinessInsightFakeStatement(array_shift($this->queue));
        }
    }
}

class BusinessInsightServiceTest extends BaseTestCase {
    private ?object $originalDatabaseInstance = null;

    protected function tearDown(): void {
        Tenant::reset();
        $this->restoreDatabaseInstance();
        parent::tearDown();
    }

    public function testGenerateForCurrentTenantReturnsEmptyWhenNoTenant(): void {
        Tenant::reset();

        $service = new BusinessInsightService();

        $this->assertSame([], $service->generateForCurrentTenant());
    }

    public function testGenerateForCompanyBuildsAndSortsInsightsByPriority(): void {
        $fakeDb = new BusinessInsightFakeDatabase();
        $fakeDb->queue = [
            12000.0,
            10000.0,
            6,
            2400.0,
            4,
            ['name' => 'Blue Widget', 'qty' => 18],
            ['revenue' => 5000.0, 'cost' => 3500.0],
            3,
        ];

        $this->swapDatabaseInstance($fakeDb);

        $service = new BusinessInsightService();
        $insights = $service->generateForCompany(44);

        $this->assertCount(6, $insights);
        $this->assertSame('low_stock', $insights[0]['type']);
        $this->assertSame('outstanding_dues', $insights[1]['type']);
        $this->assertSame('high', $insights[0]['priority']);
        $this->assertSame('high', $insights[1]['priority']);
        $this->assertSame('revenue_trend', $insights[2]['type']);
        $this->assertSame('profit_margin', $insights[4]['type']);
        $this->assertSame('slow_moving', $insights[5]['type']);
    }

    private function swapDatabaseInstance(object $fakeDb): void {
        $databaseRef = new ReflectionProperty(Database::class, 'instance');
        $databaseRef->setAccessible(true);
        $this->originalDatabaseInstance = $databaseRef->getValue();
        $databaseRef->setValue(null, $fakeDb);
    }

    private function restoreDatabaseInstance(): void {
        $databaseRef = new ReflectionProperty(Database::class, 'instance');
        $databaseRef->setAccessible(true);
        $databaseRef->setValue(null, $this->originalDatabaseInstance);
        $this->originalDatabaseInstance = null;
    }
}
