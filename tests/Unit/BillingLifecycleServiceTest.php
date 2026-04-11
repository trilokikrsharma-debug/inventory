<?php
/**
 * Unit Tests - BillingLifecycleService
 */

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/SaaSBillingHelper.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/models/TenantSubscription.php';
require_once dirname(__DIR__, 2) . '/models/PromoCode.php';
require_once dirname(__DIR__, 2) . '/models/Referral.php';
require_once dirname(__DIR__, 2) . '/services/BillingLifecycleService.php';

if (!class_exists('BillingLifecycleFakeSubscriptionModel')) {
    class BillingLifecycleFakeSubscriptionModel extends TenantSubscription {
        public array $markPaymentSuccessResult = ['success' => true];
        public ?array $findResult = null;
        public ?array $findByGatewayIdsResult = null;
        public array $markPaymentSuccessCalls = [];
        public array $updateStatusCalls = [];

        public function __construct() {}

        public function markPaymentSuccess(
            int $subscriptionId,
            string $paymentId,
            ?string $orderId,
            ?string $razorpaySubscriptionId,
            float $capturedAmount,
            string $source = 'callback'
        ): array {
            $this->markPaymentSuccessCalls[] = [
                'subscription_id' => $subscriptionId,
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'subscription_id_gateway' => $razorpaySubscriptionId,
                'captured_amount' => $capturedAmount,
                'source' => $source,
            ];

            return $this->markPaymentSuccessResult;
        }

        public function find($id) {
            return $this->findResult;
        }

        public function findByGatewayIds(?string $orderId, ?string $subscriptionGatewayId): ?array {
            return $this->findByGatewayIdsResult;
        }

        public function updateStatusByGatewaySubscription(string $gatewaySubscriptionId, string $status, ?int $companyId = null): void {
            $this->updateStatusCalls[] = [
                'gateway_subscription_id' => $gatewaySubscriptionId,
                'status' => $status,
                'company_id' => $companyId,
            ];
        }
    }
}

if (!class_exists('BillingLifecycleFakePromoModel')) {
    class BillingLifecycleFakePromoModel extends PromoCode {
        public array $usageCalls = [];

        public function __construct() {}

        public function registerUsage(
            int $promoCodeId,
            int $companyId,
            int $subscriptionId,
            float $discountAmount,
            float $finalAmount
        ): bool {
            $this->usageCalls[] = [
                'promo_code_id' => $promoCodeId,
                'company_id' => $companyId,
                'subscription_id' => $subscriptionId,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
            ];
            return true;
        }
    }
}

if (!class_exists('BillingLifecycleFakeReferralModel')) {
    class BillingLifecycleFakeReferralModel extends Referral {
        public array $markCalls = [];

        public function __construct() {}

        public function markSuccessfulAfterPayment(int $companyId, int $subscriptionId, float $paidAmount): void {
            $this->markCalls[] = [
                'company_id' => $companyId,
                'subscription_id' => $subscriptionId,
                'paid_amount' => $paidAmount,
            ];
        }
    }
}

if (!class_exists('BillingLifecycleFakeDb')) {
    class BillingLifecycleFakeDb {
        public array $queries = [];

        public function query($sql, $params = []) {
            $this->queries[] = ['sql' => $sql, 'params' => $params];
            return new class {
                public function fetch() {
                    return null;
                }
            };
        }
    }
}

class BillingLifecycleServiceTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();

        if (!defined('DATETIME_FORMAT_DB')) {
            define('DATETIME_FORMAT_DB', 'Y-m-d H:i:s');
        }
    }

    public function testFinalizePaymentSuccessAppliesPromoAndReferralEffects(): void {
        $subscriptionModel = new BillingLifecycleFakeSubscriptionModel();
        $subscriptionModel->markPaymentSuccessResult = [
            'success' => true,
            'subscription' => [
                'id' => 15,
                'company_id' => 44,
                'promo_code_id' => 9,
                'discount_amount' => 120.0,
                'amount' => 880.0,
            ],
        ];
        $promoModel = new BillingLifecycleFakePromoModel();
        $referralModel = new BillingLifecycleFakeReferralModel();

        $service = new BillingLifecycleService($subscriptionModel, $promoModel, $referralModel, new BillingLifecycleFakeDb());
        $result = $service->finalizePaymentSuccess(15, 'pay_123', 'order_123', null, 880.0);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $promoModel->usageCalls);
        $this->assertCount(1, $referralModel->markCalls);
        $this->assertSame('pay_123', $subscriptionModel->markPaymentSuccessCalls[0]['payment_id']);
    }

    public function testHandlePaymentCapturedWebhookFinalizesMatchingLocalSubscription(): void {
        $subscriptionModel = new BillingLifecycleFakeSubscriptionModel();
        $subscriptionModel->findByGatewayIdsResult = ['id' => 22, 'company_id' => 44, 'amount' => 999.0];
        $subscriptionModel->markPaymentSuccessResult = [
            'success' => true,
            'subscription' => ['id' => 22, 'company_id' => 44, 'amount' => 999.0, 'promo_code_id' => null, 'discount_amount' => 0.0],
        ];
        $service = new BillingLifecycleService(
            $subscriptionModel,
            new BillingLifecycleFakePromoModel(),
            new BillingLifecycleFakeReferralModel(),
            new BillingLifecycleFakeDb()
        );

        $service->handlePaymentCapturedWebhook([
            'id' => 'pay_22',
            'order_id' => 'order_22',
            'amount' => 99900,
        ]);

        $this->assertCount(1, $subscriptionModel->markPaymentSuccessCalls);
        $this->assertSame('webhook', $subscriptionModel->markPaymentSuccessCalls[0]['source']);
    }

    public function testHandleSubscriptionStatusWebhookMarksCompanyInactiveForCancelledStates(): void {
        $subscriptionModel = new BillingLifecycleFakeSubscriptionModel();
        $subscriptionModel->findByGatewayIdsResult = ['id' => 41, 'company_id' => 77];
        $db = new BillingLifecycleFakeDb();

        $service = new BillingLifecycleService(
            $subscriptionModel,
            new BillingLifecycleFakePromoModel(),
            new BillingLifecycleFakeReferralModel(),
            $db
        );

        $service->handleSubscriptionStatusWebhook(['id' => 'sub_77'], 'cancelled');

        $this->assertCount(1, $subscriptionModel->updateStatusCalls);
        $this->assertSame('cancelled', $subscriptionModel->updateStatusCalls[0]['status']);
        $this->assertCount(1, $db->queries);
        $this->assertSame('inactive', $db->queries[0]['params'][0]);
        $this->assertSame(77, $db->queries[0]['params'][2]);
    }

    public function testProcessWebhookEventRoutesSubscriptionChargedToFinalizationFlow(): void {
        $subscriptionModel = new BillingLifecycleFakeSubscriptionModel();
        $subscriptionModel->findByGatewayIdsResult = ['id' => 51, 'company_id' => 91, 'amount' => 1500.0];
        $subscriptionModel->markPaymentSuccessResult = [
            'success' => true,
            'subscription' => ['id' => 51, 'company_id' => 91, 'amount' => 1500.0, 'promo_code_id' => null, 'discount_amount' => 0.0],
        ];

        $service = new BillingLifecycleService(
            $subscriptionModel,
            new BillingLifecycleFakePromoModel(),
            new BillingLifecycleFakeReferralModel(),
            new BillingLifecycleFakeDb()
        );

        $service->processWebhookEvent(
            'subscription.charged',
            ['id' => 'pay_51', 'subscription_id' => 'sub_51', 'amount' => 150000],
            ['id' => 'sub_51']
        );

        $this->assertCount(1, $subscriptionModel->markPaymentSuccessCalls);
        $this->assertSame('pay_51', $subscriptionModel->markPaymentSuccessCalls[0]['payment_id']);
    }
}
