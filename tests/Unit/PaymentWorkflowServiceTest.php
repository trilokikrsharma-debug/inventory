<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/models/PaymentModel.php';
require_once dirname(__DIR__, 2) . '/models/SettingsModel.php';
require_once dirname(__DIR__, 2) . '/services/PaymentWorkflowService.php';

if (!class_exists('PaymentWorkflowFakeModel')) {
    class PaymentWorkflowFakeModel extends PaymentModel {
        public array $createCalls = [];

        public function __construct() {}

        public function createPayment($data, $userId) {
            $this->createCalls[] = ['data' => $data, 'user_id' => $userId];
            return 41;
        }
    }
}

if (!class_exists('PaymentWorkflowFakeSettingsModel')) {
    class PaymentWorkflowFakeSettingsModel extends SettingsModel {
        public array $prefixes = [];

        public function __construct() {}

        public function getNextNumber($type = 'invoice') {
            $this->prefixes[] = $type;
            return $type === 'payment' ? 'PAY-0001' : 'REC-0001';
        }
    }
}

class PaymentWorkflowServiceTest extends BaseTestCase {
    public function testCreatePaymentBuildsNormalizedReceiptPayload(): void {
        $paymentModel = new PaymentWorkflowFakeModel();
        $settingsModel = new PaymentWorkflowFakeSettingsModel();
        $service = new PaymentWorkflowService($paymentModel, $settingsModel);

        $result = $service->createPayment([
            'type' => 'receipt',
            'customer_id' => '9',
            'sale_id' => '5',
            'amount' => '12.555',
            'payment_method' => 'upi',
            'payment_date' => '2026-04-10',
            'reference_number' => ' REF-1 ',
            'bank_name' => ' <b>Axis</b> ',
            'note' => '<script>x</script>received',
        ], 7);

        $this->assertSame(['receipt'], $settingsModel->prefixes);
        $this->assertSame('REC-0001', $result['payment_number']);
        $this->assertSame(41, $result['id']);
        $this->assertCount(1, $paymentModel->createCalls);
        $this->assertSame('online', $paymentModel->createCalls[0]['data']['payment_method']);
        $this->assertSame(12.56, $paymentModel->createCalls[0]['data']['amount']);
        $this->assertSame('xreceived', $paymentModel->createCalls[0]['data']['note']);
        $this->assertSame(9, $paymentModel->createCalls[0]['data']['customer_id']);
        $this->assertSame(null, $paymentModel->createCalls[0]['data']['supplier_id']);
    }

    public function testCreatePaymentBuildsNormalizedSupplierPaymentPayload(): void {
        $paymentModel = new PaymentWorkflowFakeModel();
        $settingsModel = new PaymentWorkflowFakeSettingsModel();
        $service = new PaymentWorkflowService($paymentModel, $settingsModel);

        $result = $service->createPayment([
            'type' => 'payment',
            'supplier_id' => '11',
            'purchase_id' => '',
            'amount' => '-5',
            'payment_method' => 'wire',
            'payment_date' => '2026-04-10',
        ], 8);

        $this->assertSame(['payment'], $settingsModel->prefixes);
        $this->assertSame('PAY-0001', $result['payment_number']);
        $this->assertSame('cash', $paymentModel->createCalls[0]['data']['payment_method']);
        $this->assertSame(0.0, $paymentModel->createCalls[0]['data']['amount']);
        $this->assertSame(11, $paymentModel->createCalls[0]['data']['supplier_id']);
        $this->assertSame(null, $paymentModel->createCalls[0]['data']['customer_id']);
        $this->assertSame(null, $paymentModel->createCalls[0]['data']['purchase_id']);
    }
}
