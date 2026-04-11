<?php
/**
 * Unit Tests - BillingCheckoutService
 */

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/SaaSBillingHelper.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/models/SaaSPlan.php';
require_once dirname(__DIR__, 2) . '/models/PromoCode.php';
require_once dirname(__DIR__, 2) . '/models/TenantSubscription.php';
require_once dirname(__DIR__, 2) . '/services/BillingCheckoutService.php';

if (!class_exists('BillingCheckoutFakePlanModel')) {
    class BillingCheckoutFakePlanModel extends SaaSPlan {
        public ?array $plan = null;
        public float $checkoutPriceValue = 0.0;

        public function __construct() {}

        public function findActive(int $id): ?array {
            return $this->plan;
        }

        public function checkoutPrice($plan): float {
            return $this->checkoutPriceValue;
        }
    }
}

if (!class_exists('BillingCheckoutFakePromoModel')) {
    class BillingCheckoutFakePromoModel extends PromoCode {
        public array $validationResult = ['success' => true, 'promo' => null, 'discount_amount' => 0.0, 'final_amount' => 0.0];

        public function __construct() {}

        public function validateForCheckout(string $code, int $companyId, array $plan, float $baseAmount): array {
            return $this->validationResult;
        }
    }
}

if (!class_exists('BillingCheckoutFakeSubscriptionModel')) {
    class BillingCheckoutFakeSubscriptionModel extends TenantSubscription {
        public array $pendingResult = ['success' => true, 'subscription_id' => 15, 'order_code' => 'SUB-15'];
        public array $attachCalls = [];
        public array $failedCalls = [];

        public function __construct() {}

        public function createPendingCheckout(
            int $companyId,
            array $plan,
            float $baseAmount,
            float $discountAmount,
            float $finalAmount,
            ?array $promo = null
        ): array {
            return $this->pendingResult;
        }

        public function attachGatewayReference(
            int $subscriptionId,
            ?string $orderId,
            ?string $gatewaySubscriptionId,
            ?string $mode = null
        ): void {
            $this->attachCalls[] = [
                'subscription_id' => $subscriptionId,
                'order_id' => $orderId,
                'gateway_subscription_id' => $gatewaySubscriptionId,
                'mode' => $mode,
            ];
        }

        public function markPaymentFailed(int $subscriptionId, string $reason, ?string $paymentId = null): void {
            $this->failedCalls[] = [
                'subscription_id' => $subscriptionId,
                'reason' => $reason,
                'payment_id' => $paymentId,
            ];
        }
    }
}

if (!class_exists('BillingCheckoutFakeOrderGateway')) {
    class BillingCheckoutFakeOrderGateway {
        public array $payloads = [];
        public array $response = ['id' => 'order_123'];

        public function create(array $payload): array {
            $this->payloads[] = $payload;
            return $this->response;
        }
    }
}

if (!class_exists('BillingCheckoutFakeSubscriptionGateway')) {
    class BillingCheckoutFakeSubscriptionGateway {
        public array $payloads = [];
        public array $response = ['id' => 'sub_123'];

        public function create(array $payload): array {
            $this->payloads[] = $payload;
            return $this->response;
        }
    }
}

if (!class_exists('BillingCheckoutFakeApi')) {
    class BillingCheckoutFakeApi {
        public BillingCheckoutFakeOrderGateway $order;
        public BillingCheckoutFakeSubscriptionGateway $subscription;

        public function __construct() {
            $this->order = new BillingCheckoutFakeOrderGateway();
            $this->subscription = new BillingCheckoutFakeSubscriptionGateway();
        }
    }
}

class BillingCheckoutServiceTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();

        if (!defined('RAZORPAY_KEY')) {
            define('RAZORPAY_KEY', 'rzp_test_123');
        }
        if (!defined('APP_NAME')) {
            define('APP_NAME', 'InvenBill');
        }
    }

    public function testBuildCheckoutSessionCreatesOrderPayloadForDiscountedCheckout(): void {
        $planModel = new BillingCheckoutFakePlanModel();
        $planModel->plan = [
            'id' => 7,
            'name' => 'Professional',
            'billing_type' => 'monthly',
            'razorpay_plan_id' => 'plan_abc',
        ];
        $planModel->checkoutPriceValue = 999.00;

        $promoModel = new BillingCheckoutFakePromoModel();
        $promoModel->validationResult = [
            'success' => true,
            'promo' => ['id' => 5, 'code' => 'SAVE10'],
            'discount_amount' => 100.00,
            'final_amount' => 899.00,
        ];

        $subscriptionModel = new BillingCheckoutFakeSubscriptionModel();
        $api = new BillingCheckoutFakeApi();

        $service = new BillingCheckoutService($planModel, $promoModel, $subscriptionModel);
        $result = $service->buildCheckoutSession(
            44,
            7,
            'SAVE10',
            $api,
            ['full_name' => 'Taylor Admin', 'email' => 'taylor@example.com', 'phone' => '9999999999']
        );

        $this->assertTrue($result['success']);
        $this->assertSame('order', $result['gateway_mode']);
        $this->assertSame('SAVE10', $result['pricing']['promo_code']);
        $this->assertSame('order_123', $result['checkout']['order_id']);
        $this->assertSame('Taylor Admin', $result['checkout']['prefill']['name']);
        $this->assertCount(1, $subscriptionModel->attachCalls);
        $this->assertSame('order', $subscriptionModel->attachCalls[0]['mode']);
        $this->assertCount(1, $api->order->payloads);
        $this->assertSame(89900, $api->order->payloads[0]['amount']);
    }

    public function testBuildCheckoutSessionCreatesSubscriptionPayloadWhenEligible(): void {
        $planModel = new BillingCheckoutFakePlanModel();
        $planModel->plan = [
            'id' => 9,
            'name' => 'Enterprise',
            'billing_type' => 'yearly',
            'razorpay_plan_id' => 'plan_enterprise',
        ];
        $planModel->checkoutPriceValue = 12000.00;

        $service = new BillingCheckoutService(
            $planModel,
            new BillingCheckoutFakePromoModel(),
            $subscriptionModel = new BillingCheckoutFakeSubscriptionModel()
        );
        $api = new BillingCheckoutFakeApi();

        $result = $service->buildCheckoutSession(44, 9, '', $api, []);

        $this->assertTrue($result['success']);
        $this->assertSame('subscription', $result['gateway_mode']);
        $this->assertSame('sub_123', $result['checkout']['subscription_id']);
        $this->assertCount(1, $api->subscription->payloads);
        $this->assertSame(5, $api->subscription->payloads[0]['total_count']);
        $this->assertSame('subscription', $subscriptionModel->attachCalls[0]['mode']);
    }

    public function testBuildCheckoutSessionFailsWhenGatewayUnavailable(): void {
        $planModel = new BillingCheckoutFakePlanModel();
        $planModel->plan = [
            'id' => 3,
            'name' => 'Starter',
            'billing_type' => 'monthly',
            'razorpay_plan_id' => '',
        ];
        $planModel->checkoutPriceValue = 499.00;

        $subscriptionModel = new BillingCheckoutFakeSubscriptionModel();
        $service = new BillingCheckoutService($planModel, new BillingCheckoutFakePromoModel(), $subscriptionModel);

        $result = $service->buildCheckoutSession(44, 3, '', null, []);

        $this->assertFalse($result['success']);
        $this->assertSame('Payment gateway is not configured.', $result['message']);
        $this->assertCount(1, $subscriptionModel->failedCalls);
    }
}
