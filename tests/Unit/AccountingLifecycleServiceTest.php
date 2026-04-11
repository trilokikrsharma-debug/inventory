<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/AccountingLifecycleService.php';

if (!class_exists('AccountingLifecycleFakeStatement')) {
    class AccountingLifecycleFakeStatement {
        private $value;
        public function __construct($value) { $this->value = $value; }
        public function fetch() { return $this->value; }
        public function fetchAll() { return $this->value; }
        public function fetchColumn() { return $this->value; }
    }
}

if (!class_exists('AccountingLifecycleFakeDb')) {
    class AccountingLifecycleFakeDb {
        public array $queue = [];
        public array $queries = [];
        public function query($sql, $params = []) {
            $this->queries[] = ['sql' => $sql, 'params' => $params];
            return new AccountingLifecycleFakeStatement(array_shift($this->queue));
        }
    }
}

if (!class_exists('AccountingLifecycleFakeProductModel')) {
    class AccountingLifecycleFakeProductModel {
        public array $records = [];
        public array $setActiveCalls = [];
        public array $deleteCalls = [];
        public function find($id) { return $this->records[$id] ?? null; }
        public function setActiveState(int $id, bool $isActive): int {
            $this->setActiveCalls[] = ['id' => $id, 'is_active' => $isActive];
            return 1;
        }
        public function delete($id): int {
            $this->deleteCalls[] = $id;
            return 1;
        }
    }
}

if (!class_exists('AccountingLifecycleFakeCustomerModel')) {
    class AccountingLifecycleFakeCustomerModel {
        public array $records = [];
        public array $setActiveCalls = [];
        public array $deleteCalls = [];
        public function find($id) { return $this->records[$id] ?? null; }
        public function setActiveState(int $id, bool $isActive): int {
            $this->setActiveCalls[] = ['id' => $id, 'is_active' => $isActive];
            return 1;
        }
        public function delete($id): int {
            $this->deleteCalls[] = $id;
            return 1;
        }
    }
}

class AccountingLifecycleServiceTest extends BaseTestCase {
    public function testRetireOrDeleteProductArchivesReferencedProduct(): void {
        $db = new AccountingLifecycleFakeDb();
        $db->queue = [2, 0, 0, 0];
        $productModel = new AccountingLifecycleFakeProductModel();
        $productModel->records[9] = ['id' => 9, 'name' => 'Widget', 'is_active' => 1];
        $service = new AccountingLifecycleService($db, $productModel, new AccountingLifecycleFakeCustomerModel());

        $result = $service->retireOrDeleteProduct(9);

        $this->assertSame('archived', $result['action']);
        $this->assertSame([['id' => 9, 'is_active' => false]], $productModel->setActiveCalls);
        $this->assertSame([], $productModel->deleteCalls);
        $this->assertSame(2, $result['usage']['sales']);
    }

    public function testRetireOrDeleteProductDeletesUnusedProduct(): void {
        $db = new AccountingLifecycleFakeDb();
        $db->queue = [0, 0, 0, 0];
        $productModel = new AccountingLifecycleFakeProductModel();
        $productModel->records[11] = ['id' => 11, 'name' => 'Unused', 'is_active' => 1];
        $service = new AccountingLifecycleService($db, $productModel, new AccountingLifecycleFakeCustomerModel());

        $result = $service->retireOrDeleteProduct(11);

        $this->assertSame('deleted', $result['action']);
        $this->assertSame([11], $productModel->deleteCalls);
        $this->assertSame([], $productModel->setActiveCalls);
    }

    public function testRetireOrDeleteCustomerArchivesHistoryBearingCustomer(): void {
        $db = new AccountingLifecycleFakeDb();
        $db->queue = [1, 3, 0];
        $customerModel = new AccountingLifecycleFakeCustomerModel();
        $customerModel->records[6] = ['id' => 6, 'name' => 'Acme Retail', 'current_balance' => 120];
        $service = new AccountingLifecycleService($db, new AccountingLifecycleFakeProductModel(), $customerModel);

        $result = $service->retireOrDeleteCustomer(6);

        $this->assertSame('archived', $result['action']);
        $this->assertSame([['id' => 6, 'is_active' => false]], $customerModel->setActiveCalls);
        $this->assertSame([], $customerModel->deleteCalls);
        $this->assertSame(3, $result['usage']['payments']);
    }

    public function testSetCustomerArchivedRestoresCustomer(): void {
        $customerModel = new AccountingLifecycleFakeCustomerModel();
        $customerModel->records[4] = ['id' => 4, 'name' => 'Walk-in'];
        $service = new AccountingLifecycleService(new AccountingLifecycleFakeDb(), new AccountingLifecycleFakeProductModel(), $customerModel);

        $result = $service->setCustomerArchived(4, false);

        $this->assertTrue($result['is_active']);
        $this->assertSame([['id' => 4, 'is_active' => true]], $customerModel->setActiveCalls);
    }
}
