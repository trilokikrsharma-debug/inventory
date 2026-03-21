<?php
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

/**
 * SaaS Billing Controller
 *
 * Handles tenant subscription checkout, payment verification,
 * and Razorpay webhook processing.
 */
class SaaSBillingController extends Controller {
    protected $allowedActions = [
        'subscribe',
        'create_checkout',
        'verify_payment',
        'validate_promo',
        'cancel',
        'plans',
        'upgrade',
        'webhook',
    ];

    private SaaSPlan $planModel;
    private PromoCode $promoModel;
    private Referral $referralModel;
    private TenantSubscription $subscriptionModel;
    private const RATE_PROMO_MAX = 40;
    private const RATE_PROMO_WINDOW = 60;
    private const RATE_CHECKOUT_MAX = 15;
    private const RATE_CHECKOUT_WINDOW = 60;
    private const RATE_VERIFY_MAX = 25;
    private const RATE_VERIFY_WINDOW = 300;

    public function __construct() {
        $this->planModel = new SaaSPlan();
        $this->promoModel = new PromoCode();
        $this->referralModel = new Referral();
        $this->subscriptionModel = new TenantSubscription();
    }

    /**
     * Tenant checkout page.
     */
    public function subscribe() {
        if (!$this->ensureTenantAuth(false)) {
            return;
        }

        $companyId = (int)Tenant::id();
        $plans = $this->planModel->listForCheckout();
        $latest = $this->subscriptionModel->latestForCompany($companyId);
        $referralCode = $this->referralModel->ensureCompanyReferralCode($companyId);

        $this->view('platform.subscribe', [
            'pageTitle' => 'Choose Plan',
            'plans' => $plans,
            'latestSubscription' => $latest,
            'companyId' => $companyId,
            'referralCode' => $referralCode,
            'razorpayKey' => RAZORPAY_KEY,
            'gatewayConfigured' => $this->isGatewayConfigured(),
        ]);
    }

    /**
     * Public API: list active plans.
     */
    public function plans() {
        header('Content-Type: application/json');
        try {
            $plans = $this->planModel->listForCheckout();
            $response = [];
            foreach ($plans as $plan) {
                $plan['effective_price'] = $this->planModel->checkoutPrice($plan);
                $response[] = $plan;
            }
            echo json_encode(['success' => true, 'plans' => $response]);
        } catch (\Throwable $e) {
            Logger::error('Failed to load saas plans', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Failed to fetch plans.'], 500);
        }
    }

    /**
     * Ajax promo validator for checkout page.
     */
    public function validate_promo() {
        if (!$this->ensureTenantAuth(true)) {
            return;
        }

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }
        $companyId = (int)Tenant::id();
        if (!$this->enforceTenantRateLimit('promo_validate', $companyId, self::RATE_PROMO_MAX, self::RATE_PROMO_WINDOW)) {
            return;
        }

        $planId = (int)$this->post('plan_id');
        $code = strtoupper(trim((string)$this->post('promo_code')));
        if ($planId <= 0 || $code === '') {
            $this->json(['success' => false, 'message' => 'Plan and promo code are required.'], 422);
        }

        $plan = $this->planModel->findActive($planId);
        if (!$plan) {
            $this->json(['success' => false, 'message' => 'Plan not found or inactive.'], 404);
        }

        $base = $this->planModel->checkoutPrice($plan);
        $promoCheck = $this->promoModel->validateForCheckout($code, $companyId, $plan, $base);

        if (!$promoCheck['success']) {
            $this->json([
                'success' => false,
                'message' => $promoCheck['message'] ?? 'Invalid promo code.',
                'base_amount' => $base,
            ], 422);
        }

        $this->json([
            'success' => true,
            'message' => $promoCheck['message'],
            'promo_code' => $promoCheck['promo']['code'],
            'base_amount' => $base,
            'discount_amount' => $promoCheck['discount_amount'],
            'final_amount' => $promoCheck['final_amount'],
        ]);
    }

    /**
     * Create Razorpay order/subscription for checkout.
     */
    public function create_checkout() {
        if (!$this->ensureTenantAuth(true)) {
            return;
        }

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }
        $this->validateCSRF();

        $companyId = (int)Tenant::id();
        if (!$this->enforceTenantRateLimit('create_checkout', $companyId, self::RATE_CHECKOUT_MAX, self::RATE_CHECKOUT_WINDOW)) {
            return;
        }
        $planId = (int)$this->post('plan_id');
        $promoCode = strtoupper(trim((string)$this->post('promo_code', '')));

        $result = $this->buildCheckoutSession($companyId, $planId, $promoCode);
        if (!$result['success']) {
            $this->json($result, 422);
        }
        $this->json($result);
    }

    /**
     * API compatible upgrade endpoint.
     * POST /api/v1/tenant/subscription/upgrade
     */
    public function upgrade() {
        if (!$this->ensureTenantAuth(true)) {
            return;
        }

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }
        $this->validateCSRF();

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $planId = (int)($input['plan_id'] ?? 0);
        $promoCode = strtoupper(trim((string)($input['promo_code'] ?? '')));
        $companyId = (int)Tenant::id();
        if (!$this->enforceTenantRateLimit('upgrade_api', $companyId, self::RATE_CHECKOUT_MAX, self::RATE_CHECKOUT_WINDOW)) {
            return;
        }

        $result = $this->buildCheckoutSession($companyId, $planId, $promoCode);
        if (!$result['success']) {
            $this->json($result, 422);
        }
        $this->json($result);
    }

    /**
     * Verify callback payment signature and finalize subscription.
     */
    public function verify_payment() {
        if (!$this->ensureTenantAuth(true)) {
            return;
        }
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }
        $this->validateCSRF();

        $companyId = (int)Tenant::id();
        if (!$this->enforceTenantRateLimit('verify_payment', $companyId, self::RATE_VERIFY_MAX, self::RATE_VERIFY_WINDOW)) {
            return;
        }
        $localSubscriptionId = (int)$this->post('local_subscription_id');
        $razorpayPaymentId = trim((string)$this->post('razorpay_payment_id'));
        $razorpayOrderId = trim((string)$this->post('razorpay_order_id'));
        $razorpaySubscriptionId = trim((string)$this->post('razorpay_subscription_id'));
        $signature = trim((string)$this->post('razorpay_signature'));

        if ($localSubscriptionId <= 0 || $razorpayPaymentId === '' || $signature === '') {
            $this->json(['success' => false, 'message' => 'Incomplete payment verification payload.'], 422);
        }

        $local = $this->subscriptionModel->findForCompany($localSubscriptionId, $companyId);
        if (!$local) {
            Logger::security('Subscription ownership mismatch on verify', [
                'company_id' => $companyId,
                'subscription_id' => $localSubscriptionId,
            ]);
            $this->json(['success' => false, 'message' => 'Invalid subscription reference.'], 403);
        }

        // Tamper checks for gateway references.
        if (!empty($local['razorpay_order_id']) && $razorpayOrderId !== '' && $local['razorpay_order_id'] !== $razorpayOrderId) {
            Logger::security('Order id mismatch on verify', [
                'subscription_id' => $localSubscriptionId,
                'expected' => $local['razorpay_order_id'],
                'provided' => $razorpayOrderId,
            ]);
            $this->json(['success' => false, 'message' => 'Payment reference mismatch.'], 403);
        }

        if (!empty($local['razorpay_subscription_id']) && $razorpaySubscriptionId !== '' && $local['razorpay_subscription_id'] !== $razorpaySubscriptionId) {
            Logger::security('Gateway subscription id mismatch on verify', [
                'subscription_id' => $localSubscriptionId,
                'expected' => $local['razorpay_subscription_id'],
                'provided' => $razorpaySubscriptionId,
            ]);
            $this->json(['success' => false, 'message' => 'Subscription reference mismatch.'], 403);
        }

        $api = $this->razorpay();
        if (!$api) {
            $this->json(['success' => false, 'message' => 'Payment gateway is not configured.'], 500);
        }

        try {
            if ($razorpayOrderId !== '') {
                $api->utility->verifyPaymentSignature([
                    'razorpay_order_id' => $razorpayOrderId,
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'razorpay_signature' => $signature,
                ]);
            } else {
                $api->utility->verifyPaymentSignature([
                    'razorpay_subscription_id' => $razorpaySubscriptionId,
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'razorpay_signature' => $signature,
                ]);
            }
        } catch (SignatureVerificationError $e) {
            Logger::security('Payment signature verification failed', [
                'subscription_id' => $localSubscriptionId,
                'payment_id' => $razorpayPaymentId,
                'error' => $e->getMessage(),
            ]);
            $this->subscriptionModel->markPaymentFailed($localSubscriptionId, 'Signature verification failed', $razorpayPaymentId);
            $this->json(['success' => false, 'message' => 'Payment signature verification failed.'], 403);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Unable to verify payment signature.'], 500);
        }

        try {
            $payment = $api->payment->fetch($razorpayPaymentId);
            $status = strtolower((string)($payment['status'] ?? ''));
            if ($status === 'authorized') {
                Logger::warning('Payment authorized but not captured yet', [
                    'subscription_id' => $localSubscriptionId,
                    'payment_id' => $razorpayPaymentId,
                ]);
                $this->json([
                    'success' => false,
                    'pending_capture' => true,
                    'message' => 'Payment authorized. Waiting for capture confirmation.',
                ], 409);
            }

            if ($status !== 'captured') {
                $this->subscriptionModel->markPaymentFailed($localSubscriptionId, 'Payment status: ' . $status, $razorpayPaymentId);
                $this->json(['success' => false, 'message' => 'Payment is not captured yet.'], 422);
            }

            $captured = SaaSBillingHelper::money(((float)($payment['amount'] ?? 0)) / 100);

            $finalize = $this->subscriptionModel->markPaymentSuccess(
                $localSubscriptionId,
                $razorpayPaymentId,
                $razorpayOrderId !== '' ? $razorpayOrderId : null,
                $razorpaySubscriptionId !== '' ? $razorpaySubscriptionId : null,
                $captured,
                'callback'
            );

            if (!$finalize['success']) {
                $this->json(['success' => false, 'message' => $finalize['message'] ?? 'Failed to finalize payment.'], 422);
            }

            $updated = $finalize['subscription'] ?? $this->subscriptionModel->find($localSubscriptionId);
            if (!empty($updated['promo_code_id'])) {
                $this->promoModel->registerUsage(
                    (int)$updated['promo_code_id'],
                    (int)$updated['company_id'],
                    (int)$updated['id'],
                    (float)$updated['discount_amount'],
                    (float)$updated['amount']
                );
            }

            $this->referralModel->markSuccessfulAfterPayment(
                (int)$updated['company_id'],
                (int)$updated['id'],
                (float)$updated['amount']
            );

            $this->logActivity('SaaS payment verified', 'saas_billing', $localSubscriptionId, 'Payment ID: ' . $razorpayPaymentId);

            $this->json([
                'success' => true,
                'message' => !empty($finalize['already_processed'])
                    ? 'Payment already processed.'
                    : 'Subscription activated successfully.',
                'subscription_id' => (int)$updated['id'],
            ]);
        } catch (\Throwable $e) {
            Logger::error('Payment verification processing failed', [
                'subscription_id' => $localSubscriptionId,
                'payment_id' => $razorpayPaymentId,
                'error' => $e->getMessage(),
            ]);
            $this->subscriptionModel->markPaymentFailed($localSubscriptionId, 'Verification processing failed', $razorpayPaymentId);
            $this->json(['success' => false, 'message' => 'Failed to finalize subscription.'], 500);
        }
    }

    /**
     * Cancel local + gateway subscription.
     */
    public function cancel() {
        if (!$this->ensureTenantAuth(false)) {
            return;
        }
        if (!$this->isPost()) {
            $this->redirect('index.php?page=saas_billing&action=subscribe');
            return;
        }
        $this->validateCSRF();
        if ($this->demoGuard()) {
            return;
        }

        $companyId = (int)Tenant::id();
        $subscriptionId = (int)$this->post('subscription_id');
        $sub = $this->subscriptionModel->findForCompany($subscriptionId, $companyId);
        if (!$sub) {
            $this->setFlash('error', 'Subscription not found.');
            $this->redirect('index.php?page=saas_billing&action=subscribe');
            return;
        }

        if (!empty($sub['razorpay_subscription_id'])) {
            $api = $this->razorpay();
            if ($api) {
                try {
                    $api->subscription->fetch($sub['razorpay_subscription_id'])->cancel(['cancel_at_cycle_end' => 1]);
                } catch (\Throwable $e) {
                    Logger::warning('Gateway cancel failed; local cancel continues', [
                        'subscription_id' => $subscriptionId,
                        'gateway_subscription_id' => $sub['razorpay_subscription_id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $ok = $this->subscriptionModel->markCancelled($subscriptionId, $companyId);
        if ($ok) {
            $this->logActivity('SaaS subscription cancelled', 'saas_billing', $subscriptionId);
            $this->setFlash('success', 'Subscription cancelled successfully.');
        } else {
            $this->setFlash('error', 'Unable to cancel subscription.');
        }
        $this->redirect('index.php?page=saas_billing&action=subscribe');
    }

    /**
     * Razorpay webhook endpoint
     * POST /api/v1/saas/webhook
     */
    public function webhook() {
        if (!$this->isPost()) {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            return;
        }

        header('Content-Type: application/json');
        $payload = file_get_contents('php://input') ?: '';
        $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

        if (empty(RAZORPAY_WEBHOOK_SECRET)) {
            Logger::critical('Razorpay webhook secret is missing.');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Webhook secret not configured.']);
            return;
        }

        try {
            $api = $this->razorpay();
            if (!$api) {
                throw new \RuntimeException('Razorpay keys are not configured.');
            }
            $api->utility->verifyWebhookSignature($payload, $signature, RAZORPAY_WEBHOOK_SECRET);
        } catch (\Throwable $e) {
            Logger::security('Webhook signature verification failed', ['error' => $e->getMessage()]);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid webhook signature.']);
            return;
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid payload.']);
            return;
        }

        $event = (string)($data['event'] ?? '');
        $paymentEntity = $data['payload']['payment']['entity'] ?? [];
        $subscriptionEntity = $data['payload']['subscription']['entity'] ?? [];

        $eventUnique = $event . ':' . (
            ($paymentEntity['id'] ?? '') .
            '|' . ($paymentEntity['order_id'] ?? '') .
            '|' . ($paymentEntity['subscription_id'] ?? '') .
            '|' . ($subscriptionEntity['id'] ?? '') .
            '|' . substr(hash('sha256', $payload), 0, 16)
        );

        if (!$this->subscriptionModel->registerWebhookEvent($eventUnique, $event, $payload, $signature)) {
            echo json_encode(['success' => true, 'message' => 'Duplicate webhook ignored.']);
            return;
        }

        try {
            switch ($event) {
                case 'payment.captured':
                    $this->handlePaymentCapturedWebhook($paymentEntity);
                    break;

                case 'subscription.activated':
                    $this->handleSubscriptionStatusWebhook($subscriptionEntity, 'active');
                    break;

                case 'subscription.charged':
                    $this->handleSubscriptionChargedWebhook($subscriptionEntity, $paymentEntity);
                    break;

                case 'subscription.cancelled':
                    $this->handleSubscriptionStatusWebhook($subscriptionEntity, 'cancelled');
                    break;

                case 'subscription.completed':
                    $this->handleSubscriptionStatusWebhook($subscriptionEntity, 'completed');
                    break;

                case 'subscription.halted':
                    $this->handleSubscriptionStatusWebhook($subscriptionEntity, 'halted');
                    break;

                default:
                    Logger::info('Unhandled Razorpay webhook event', ['event' => $event]);
                    break;
            }

            $this->subscriptionModel->markWebhookProcessed($eventUnique, 'processed');
            echo json_encode(['success' => true, 'message' => 'Webhook processed.']);
        } catch (\Throwable $e) {
            $this->subscriptionModel->markWebhookProcessed($eventUnique, 'failed', $e->getMessage());
            Logger::error('Webhook processing failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Webhook processing failed.']);
        }
    }

    /**
     * Build server-trusted checkout session and gateway payload.
     */
    private function buildCheckoutSession(int $companyId, int $planId, string $promoCode = ''): array {
        if ($planId <= 0) {
            return ['success' => false, 'message' => 'Plan id is required.'];
        }

        $plan = $this->planModel->findActive($planId);
        if (!$plan) {
            return ['success' => false, 'message' => 'Plan not found or inactive.'];
        }

        $baseAmount = $this->planModel->checkoutPrice($plan);
        $discountAmount = 0.00;
        $finalAmount = max(SaaSBillingHelper::MIN_PAYABLE, $baseAmount);
        $promo = null;

        if ($promoCode !== '') {
            $promoCheck = $this->promoModel->validateForCheckout($promoCode, $companyId, $plan, $baseAmount);
            if (!$promoCheck['success']) {
                return ['success' => false, 'message' => $promoCheck['message'] ?? 'Promo validation failed.'];
            }
            $promo = $promoCheck['promo'];
            $discountAmount = (float)$promoCheck['discount_amount'];
            $finalAmount = (float)$promoCheck['final_amount'];
        }

        $pending = $this->subscriptionModel->createPendingCheckout(
            $companyId,
            $plan,
            $baseAmount,
            $discountAmount,
            $finalAmount,
            $promo
        );
        if (!$pending['success']) {
            return $pending;
        }

        $api = $this->razorpay();
        if (!$api) {
            $this->subscriptionModel->markPaymentFailed(
                (int)$pending['subscription_id'],
                'Razorpay key/secret not configured'
            );
            return ['success' => false, 'message' => 'Payment gateway is not configured.'];
        }

        $billingType = (string)($plan['billing_type'] ?? 'monthly');
        $gatewayMode = 'order';
        $checkoutPayload = null;

        try {
            $canUseSubscriptionGateway =
                in_array($billingType, ['monthly', 'yearly'], true) &&
                !empty($plan['razorpay_plan_id']) &&
                empty($promo) &&
                abs($finalAmount - $baseAmount) <= 0.01;

            if ($canUseSubscriptionGateway) {
                $gatewayMode = 'subscription';
                $totalCount = $billingType === 'yearly' ? 5 : 60;
                $subscription = $api->subscription->create([
                    'plan_id' => (string)$plan['razorpay_plan_id'],
                    'customer_notify' => 1,
                    'quantity' => 1,
                    'total_count' => $totalCount,
                    'notes' => [
                        'local_subscription_id' => (string)$pending['subscription_id'],
                        'company_id' => (string)$companyId,
                        'plan_id' => (string)$plan['id'],
                    ],
                ]);

                $this->subscriptionModel->attachGatewayReference(
                    (int)$pending['subscription_id'],
                    null,
                    (string)$subscription['id'],
                    'subscription'
                );

                $checkoutPayload = [
                    'key' => RAZORPAY_KEY,
                    'name' => APP_NAME,
                    'description' => $plan['name'] . ' Plan',
                    'subscription_id' => (string)$subscription['id'],
                    'notes' => [
                        'local_subscription_id' => (string)$pending['subscription_id'],
                    ],
                    'theme' => ['color' => '#0d6efd'],
                ];
            } else {
                $gatewayMode = 'order';
                $order = $api->order->create([
                    'receipt' => (string)$pending['order_code'],
                    'amount' => SaaSBillingHelper::toPaise($finalAmount),
                    'currency' => 'INR',
                    'notes' => [
                        'local_subscription_id' => (string)$pending['subscription_id'],
                        'company_id' => (string)$companyId,
                        'plan_id' => (string)$plan['id'],
                    ],
                ]);

                $this->subscriptionModel->attachGatewayReference(
                    (int)$pending['subscription_id'],
                    (string)$order['id'],
                    null,
                    'order'
                );

                $checkoutPayload = [
                    'key' => RAZORPAY_KEY,
                    'name' => APP_NAME,
                    'description' => $plan['name'] . ' Plan',
                    'order_id' => (string)$order['id'],
                    'amount' => SaaSBillingHelper::toPaise($finalAmount),
                    'currency' => 'INR',
                    'notes' => [
                        'local_subscription_id' => (string)$pending['subscription_id'],
                    ],
                    'theme' => ['color' => '#0d6efd'],
                ];
            }

            $user = Session::get('user') ?? [];
            $checkoutPayload['prefill'] = [
                'name' => (string)($user['full_name'] ?? $user['name'] ?? ''),
                'email' => (string)($user['email'] ?? ''),
                'contact' => (string)($user['phone'] ?? ''),
            ];

            return [
                'success' => true,
                'message' => 'Checkout created successfully.',
                'gateway_mode' => $gatewayMode,
                'local_subscription_id' => (int)$pending['subscription_id'],
                'plan' => [
                    'id' => (int)$plan['id'],
                    'name' => (string)$plan['name'],
                    'billing_type' => (string)$plan['billing_type'],
                ],
                'pricing' => [
                    'base_amount' => SaaSBillingHelper::money($baseAmount),
                    'discount_amount' => SaaSBillingHelper::money($discountAmount),
                    'final_amount' => SaaSBillingHelper::money($finalAmount),
                    'promo_code' => $promo['code'] ?? null,
                ],
                'checkout' => $checkoutPayload,
            ];
        } catch (\Throwable $e) {
            $this->subscriptionModel->markPaymentFailed(
                (int)$pending['subscription_id'],
                'Gateway creation failed: ' . $e->getMessage()
            );
            Logger::error('Gateway checkout creation failed', [
                'company_id' => $companyId,
                'plan_id' => $planId,
                'gateway_mode' => $gatewayMode,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Could not initiate Razorpay checkout.'];
        }
    }

    private function handlePaymentCapturedWebhook(array $paymentEntity): void {
        $paymentId = (string)($paymentEntity['id'] ?? '');
        if ($paymentId === '') {
            return;
        }

        $orderId = (string)($paymentEntity['order_id'] ?? '');
        $subscriptionId = (string)($paymentEntity['subscription_id'] ?? '');
        $amount = SaaSBillingHelper::money(((float)($paymentEntity['amount'] ?? 0)) / 100);

        $local = $this->subscriptionModel->findByGatewayIds($orderId ?: null, $subscriptionId ?: null);
        if (!$local) {
            Logger::security('Webhook payment captured with no local subscription', [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'gateway_subscription_id' => $subscriptionId,
            ]);
            return;
        }

        $result = $this->subscriptionModel->markPaymentSuccess(
            (int)$local['id'],
            $paymentId,
            $orderId !== '' ? $orderId : null,
            $subscriptionId !== '' ? $subscriptionId : null,
            $amount,
            'webhook'
        );

        if (!empty($result['success'])) {
            $updated = $result['subscription'] ?? $local;
            if (!empty($updated['promo_code_id'])) {
                $this->promoModel->registerUsage(
                    (int)$updated['promo_code_id'],
                    (int)$updated['company_id'],
                    (int)$updated['id'],
                    (float)$updated['discount_amount'],
                    (float)$updated['amount']
                );
            }
            $this->referralModel->markSuccessfulAfterPayment(
                (int)$updated['company_id'],
                (int)$updated['id'],
                (float)$updated['amount']
            );
        }
    }

    private function handleSubscriptionChargedWebhook(array $subscriptionEntity, array $paymentEntity): void {
        $gatewaySubId = (string)($subscriptionEntity['id'] ?? $paymentEntity['subscription_id'] ?? '');
        $orderId = (string)($paymentEntity['order_id'] ?? '');
        $paymentId = (string)($paymentEntity['id'] ?? '');
        if ($gatewaySubId === '' || $paymentId === '') {
            return;
        }

        $amount = SaaSBillingHelper::money(((float)($paymentEntity['amount'] ?? 0)) / 100);
        $local = $this->subscriptionModel->findByGatewayIds($orderId ?: null, $gatewaySubId);
        if (!$local) {
            Logger::warning('subscription.charged webhook with missing local subscription', [
                'gateway_subscription_id' => $gatewaySubId,
                'payment_id' => $paymentId,
            ]);
            return;
        }

        $this->subscriptionModel->markPaymentSuccess(
            (int)$local['id'],
            $paymentId,
            $orderId !== '' ? $orderId : null,
            $gatewaySubId,
            $amount,
            'webhook'
        );
    }

    private function handleSubscriptionStatusWebhook(array $subscriptionEntity, string $status): void {
        $gatewaySubId = (string)($subscriptionEntity['id'] ?? '');
        if ($gatewaySubId === '') {
            return;
        }

        $this->subscriptionModel->updateStatusByGatewaySubscription($gatewaySubId, $status);
        $local = $this->subscriptionModel->findByGatewayIds(null, $gatewaySubId);
        if (!$local) {
            return;
        }

        if (in_array($status, ['cancelled', 'halted', 'completed'], true)) {
            $companyStatus = $status === 'completed' ? 'inactive' : 'inactive';
            Database::getInstance()->query(
                "UPDATE companies SET subscription_status = ?, updated_at = ? WHERE id = ?",
                [$companyStatus, SaaSBillingHelper::now(), (int)$local['company_id']]
            );
        } elseif ($status === 'active') {
            Database::getInstance()->query(
                "UPDATE companies SET subscription_status = 'active', updated_at = ? WHERE id = ?",
                [SaaSBillingHelper::now(), (int)$local['company_id']]
            );
        }
    }

    private function ensureTenantAuth(bool $jsonOnFail = false): bool {
        if (!Session::isLoggedIn()) {
            if ($jsonOnFail) {
                $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
            }
            Session::setFlash('error', 'Please login to continue.');
            $this->redirect('index.php?page=login');
            return false;
        }

        if (Session::isSuperAdmin() || Tenant::id() === null) {
            if ($jsonOnFail) {
                $this->json(['success' => false, 'message' => 'Tenant context required.'], 403);
            }
            Session::setFlash('error', 'Tenant context required.');
            $this->redirect('index.php?page=dashboard');
            return false;
        }

        return true;
    }

    private function razorpay(): ?Api {
        if (!$this->isGatewayConfigured()) {
            return null;
        }
        $key = trim((string)RAZORPAY_KEY);
        $secret = trim((string)RAZORPAY_SECRET);
        return new Api($key, $secret);
    }

    private function isGatewayConfigured(): bool {
        $key = trim((string)RAZORPAY_KEY);
        $secret = trim((string)RAZORPAY_SECRET);

        if ($key === '' || $secret === '') {
            return false;
        }

        if (stripos($key, 'placeholder') !== false || stripos($secret, 'placeholder') !== false) {
            return false;
        }

        return true;
    }

    private function enforceTenantRateLimit(string $bucket, int $companyId, int $maxHits, int $windowSeconds): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = 'billing:' . $bucket . ':c' . $companyId . ':ip:' . $ip;
        $allowed = RateLimiter::attempt($key, $maxHits, $windowSeconds);
        RateLimiter::headers($key, $maxHits, $windowSeconds);

        if ($allowed) {
            return true;
        }

        Logger::security('Billing rate limit exceeded', [
            'bucket' => $bucket,
            'company_id' => $companyId,
            'ip' => $ip,
            'max_hits' => $maxHits,
            'window_seconds' => $windowSeconds,
        ]);

        $this->json([
            'success' => false,
            'message' => 'Too many requests. Please wait and retry.',
        ], 429);
        return false;
    }
}
