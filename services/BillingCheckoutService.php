<?php
/**
 * Trusted checkout session builder for SaaS billing.
 *
 * Keeps plan/promo pricing validation and Razorpay checkout payload assembly
 * out of the controller so the controller only handles request flow.
 */
class BillingCheckoutService {
    private SaaSPlan $planModel;
    private PromoCode $promoModel;
    private TenantSubscription $subscriptionModel;

    public function __construct(SaaSPlan $planModel, PromoCode $promoModel, TenantSubscription $subscriptionModel) {
        $this->planModel = $planModel;
        $this->promoModel = $promoModel;
        $this->subscriptionModel = $subscriptionModel;
    }

    /**
     * @param object $api Razorpay API client
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function buildCheckoutSession(int $companyId, int $planId, string $promoCode, $api, array $user = []): array {
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

        if (!$api) {
            $this->subscriptionModel->markPaymentFailed(
                (int)$pending['subscription_id'],
                'Razorpay key/secret not configured'
            );
            return ['success' => false, 'message' => 'Payment gateway is not configured.'];
        }

        $billingType = (string)($plan['billing_type'] ?? 'monthly');
        $gatewayMode = 'order';

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
}
