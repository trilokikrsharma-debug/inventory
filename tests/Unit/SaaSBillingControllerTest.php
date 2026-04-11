<?php
/**
 * Unit Tests - SaaSBillingController guards
 */

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/core/Session.php';
require_once dirname(__DIR__, 2) . '/core/Controller.php';
require_once dirname(__DIR__, 2) . '/core/RateLimiter.php';
require_once dirname(__DIR__, 2) . '/core/Logger.php';
require_once dirname(__DIR__, 2) . '/core/SaaSBillingHelper.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/models/SaaSPlan.php';
require_once dirname(__DIR__, 2) . '/models/PromoCode.php';
require_once dirname(__DIR__, 2) . '/models/Referral.php';
require_once dirname(__DIR__, 2) . '/models/TenantSubscription.php';
require_once dirname(__DIR__, 2) . '/controllers/SaaSBillingController.php';

class SaaSBillingControllerTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();

        if (!defined('RAZORPAY_KEY')) {
            define('RAZORPAY_KEY', '');
        }
        if (!defined('RAZORPAY_SECRET')) {
            define('RAZORPAY_SECRET', '');
        }
        if (!defined('RAZORPAY_WEBHOOK_SECRET')) {
            define('RAZORPAY_WEBHOOK_SECRET', '');
        }
    }

    protected function tearDown(): void {
        Tenant::reset();
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['REMOTE_ADDR']);
        parent::tearDown();
    }

    public function testVerifyPaymentRejectsIncompletePayload(): void {
        Session::set('user', ['id' => 7, 'company_id' => 44, 'is_super_admin' => 0]);
        Tenant::set(44, ['id' => 44]);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_POST = [
            'local_subscription_id' => 15,
            'razorpay_payment_id' => '',
            'razorpay_signature' => '',
        ];

        $controller = $this->makeBillingController(new FakeTenantSubscriptionForBilling());

        try {
            $controller->verify_payment();
            $this->fail('Expected JSON short-circuit.');
        } catch (BillingJsonResponse $response) {
            $this->assertSame(422, $response->statusCode);
            $this->assertFalse($response->payload['success']);
            $this->assertSame('Incomplete payment verification payload.', $response->payload['message']);
        }
    }

    public function testVerifyPaymentRejectsCrossTenantSubscriptionReference(): void {
        Session::set('user', ['id' => 7, 'company_id' => 44, 'is_super_admin' => 0]);
        Tenant::set(44, ['id' => 44]);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_POST = [
            'local_subscription_id' => 99,
            'razorpay_payment_id' => 'pay_123',
            'razorpay_order_id' => 'order_123',
            'razorpay_signature' => 'sig_123',
        ];

        $subscriptionModel = new FakeTenantSubscriptionForBilling();
        $subscriptionModel->findForCompanyResult = null;
        $controller = $this->makeBillingController($subscriptionModel);

        try {
            $controller->verify_payment();
            $this->fail('Expected JSON short-circuit.');
        } catch (BillingJsonResponse $response) {
            $this->assertSame(403, $response->statusCode);
            $this->assertFalse($response->payload['success']);
            $this->assertSame('Invalid subscription reference.', $response->payload['message']);
        }
    }

    private function makeBillingController(FakeTenantSubscriptionForBilling $subscriptionModel): TestableSaaSBillingController {
        $controller = new TestableSaaSBillingController();

        $planProperty = new ReflectionProperty(SaaSBillingController::class, 'planModel');
        $promoProperty = new ReflectionProperty(SaaSBillingController::class, 'promoModel');
        $referralProperty = new ReflectionProperty(SaaSBillingController::class, 'referralModel');
        $subscriptionProperty = new ReflectionProperty(SaaSBillingController::class, 'subscriptionModel');

        $planProperty->setAccessible(true);
        $promoProperty->setAccessible(true);
        $referralProperty->setAccessible(true);
        $subscriptionProperty->setAccessible(true);

        $planProperty->setValue($controller, new FakeSaaSPlanForBilling());
        $promoProperty->setValue($controller, new FakePromoCodeForBilling());
        $referralProperty->setValue($controller, new FakeReferralForBilling());
        $subscriptionProperty->setValue($controller, $subscriptionModel);

        return $controller;
    }
}

class BillingJsonResponse extends RuntimeException {
    public int $statusCode;
    /** @var array<string, mixed> */
    public array $payload;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(int $statusCode, array $payload) {
        parent::__construct('billing-json');
        $this->statusCode = $statusCode;
        $this->payload = $payload;
    }
}

class TestableSaaSBillingController extends SaaSBillingController {
    public function __construct() {}

    protected function json($data, $statusCode = 200) {
        throw new BillingJsonResponse($statusCode, is_array($data) ? $data : ['value' => $data]);
    }

    protected function redirect($url) {
        throw new RuntimeException('Unexpected redirect: ' . $url);
    }

    protected function validateCSRF() {
        return;
    }
}

class FakeTenantSubscriptionForBilling extends TenantSubscription {
    public ?array $findForCompanyResult = null;

    public function __construct() {}

    public function findForCompany(int $subscriptionId, int $companyId): ?array {
        return $this->findForCompanyResult;
    }

    public function markPaymentFailed(int $subscriptionId, string $reason, ?string $paymentId = null): void {
        return;
    }
}

class FakeSaaSPlanForBilling extends SaaSPlan {
    public function __construct() {}
}

class FakePromoCodeForBilling extends PromoCode {
    public function __construct() {}
}

class FakeReferralForBilling extends Referral {
    public function __construct() {}
}
