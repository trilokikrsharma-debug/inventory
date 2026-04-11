<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/models/SupplierModel.php';
require_once dirname(__DIR__, 2) . '/services/SupplierWorkflowService.php';

if (!class_exists('SupplierWorkflowFakeModel')) {
    class SupplierWorkflowFakeModel extends SupplierModel {
        public array $createPayloads = [];

        public function __construct() {}

        public function create($data) {
            $this->createPayloads[] = $data;
            return count($this->createPayloads);
        }
    }
}

class SupplierWorkflowServiceTest extends BaseTestCase {
    public function testBuildPayloadNormalizesSupplierFields(): void {
        $service = new SupplierWorkflowService(new SupplierWorkflowFakeModel());
        $payload = $service->buildPayload([
            'name' => ' <b>Acme Supply</b> ',
            'email' => ' SALES@EXAMPLE.COM ',
            'phone' => ' 12345 ',
            'address' => '<script>x</script>Warehouse Road',
            'city' => ' Pune ',
            'state' => ' Maharashtra ',
            'zip' => ' 411001 ',
            'tax_number' => ' gst-9 ',
            'opening_balance' => '12.5',
        ], true);

        $this->assertSame('Acme Supply', $payload['name']);
        $this->assertSame('sales@example.com', $payload['email']);
        $this->assertSame('12345', $payload['phone']);
        $this->assertSame('xWarehouse Road', $payload['address']);
        $this->assertSame('GST-9', $payload['tax_number']);
        $this->assertSame(12.5, $payload['opening_balance']);
        $this->assertSame(12.5, $payload['current_balance']);
    }

    public function testPersistImportedContactsCreatesNormalizedSuppliers(): void {
        $model = new SupplierWorkflowFakeModel();
        $service = new SupplierWorkflowService($model);

        $count = $service->persistImportedContacts([[
            'normalized' => [
                'name' => 'Imported Supplier',
                'email' => 'IMPORT@EXAMPLE.COM',
                'phone' => '555',
                'tax_number' => 'ab-12',
                'opening_balance' => 9,
                'current_balance' => 7,
            ],
        ]]);

        $this->assertSame(1, $count);
        $this->assertCount(1, $model->createPayloads);
        $this->assertSame('import@example.com', $model->createPayloads[0]['email']);
        $this->assertSame('AB-12', $model->createPayloads[0]['tax_number']);
        $this->assertSame(7.0, $model->createPayloads[0]['current_balance']);
    }
}
