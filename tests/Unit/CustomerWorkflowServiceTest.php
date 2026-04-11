<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/models/CustomerModel.php';
require_once dirname(__DIR__, 2) . '/services/CustomerWorkflowService.php';

if (!class_exists('CustomerWorkflowFakeModel')) {
    class CustomerWorkflowFakeModel extends CustomerModel {
        public array $createPayloads = [];
        public function __construct() {}
        public function create($data) {
            $this->createPayloads[] = $data;
            return count($this->createPayloads);
        }
    }
}

class CustomerWorkflowServiceTest extends BaseTestCase {
    public function testBuildPayloadNormalizesCustomerFields(): void {
        $service = new CustomerWorkflowService(new CustomerWorkflowFakeModel());
        $payload = $service->buildPayload([
            'name' => ' <b>Acme</b> ',
            'email' => ' TEST@Example.com ',
            'phone' => ' 9999999999 ',
            'address' => '<script>x</script>Street',
            'tax_number' => 'ab-1234',
            'opening_balance' => '12.556',
        ]);

        $this->assertSame('Acme', $payload['name']);
        $this->assertSame('test@example.com', $payload['email']);
        $this->assertSame('9999999999', $payload['phone']);
        $this->assertSame('xStreet', $payload['address']);
        $this->assertSame('AB-1234', $payload['tax_number']);
        $this->assertSame(12.56, $payload['opening_balance']);
        $this->assertSame(12.56, $payload['current_balance']);
    }

    public function testPersistImportedContactsCreatesEachNormalizedCustomer(): void {
        $model = new CustomerWorkflowFakeModel();
        $service = new CustomerWorkflowService($model);

        $count = $service->persistImportedContacts([
            ['normalized' => ['name' => 'Alpha', 'email' => 'a@example.com', 'opening_balance' => 10, 'current_balance' => 10]],
            ['normalized' => ['name' => 'Beta', 'phone' => '1234567', 'opening_balance' => 0, 'current_balance' => 0]],
        ]);

        $this->assertSame(2, $count);
        $this->assertCount(2, $model->createPayloads);
        $this->assertSame('Alpha', $model->createPayloads[0]['name']);
        $this->assertSame('Beta', $model->createPayloads[1]['name']);
    }
}
