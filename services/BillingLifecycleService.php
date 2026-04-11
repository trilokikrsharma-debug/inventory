<?php
/**
 * Billing payment-finalization and webhook lifecycle service.
 *
 * Centralizes subscription success transitions and webhook-driven state
 * updates so the controller only handles transport/gateway concerns.
 */
class BillingLifecycleService {
    private TenantSubscription $subscriptionModel;
    private PromoCode $promoModel;
    private Referral $referralModel;
    private $db;

    public function __construct(
        TenantSubscription $subscriptionModel,
        PromoCode $promoModel,
        Referral $referralModel,
        $db = null
    ) {
        $this->subscriptionModel = $subscriptionModel;
        $this->promoModel = $promoModel;
        $this->referralModel = $referralModel;
        $this->db = $db ?: Database::getInstance();
    }

    public function finalizePaymentSuccess(
        int $subscriptionId,
        string $paymentId,
        ?string $orderId,
        ?string $gatewaySubscriptionId,
        float $capturedAmount,
        string $source = 'callback'
    ): array {
        $result = $this->subscriptionModel->markPaymentSuccess(
            $subscriptionId,
            $paymentId,
            $orderId,
            $gatewaySubscriptionId,
            $capturedAmount,
            $source
        );

        if (empty($result['success'])) {
            return $result;
        }

        $updated = $result['subscription'] ?? $this->subscriptionModel->find($subscriptionId);
        if (is_array($updated)) {
            $this->applyPostPaymentEffects($updated);
            $result['subscription'] = $updated;
        }

        return $result;
    }

    public function processWebhookEvent(string $event, array $paymentEntity, array $subscriptionEntity): void {
        switch ($event) {
            case 'payment.captured':
                $this->handlePaymentCapturedWebhook($paymentEntity);
                return;

            case 'subscription.activated':
                $this->handleSubscriptionStatusWebhook($subscriptionEntity, 'active');
                return;

            case 'subscription.charged':
                $this->handleSubscriptionChargedWebhook($subscriptionEntity, $paymentEntity);
                return;

            case 'subscription.cancelled':
                $this->handleSubscriptionStatusWebhook($subscriptionEntity, 'cancelled');
                return;

            case 'subscription.completed':
                $this->handleSubscriptionStatusWebhook($subscriptionEntity, 'completed');
                return;

            case 'subscription.halted':
                $this->handleSubscriptionStatusWebhook($subscriptionEntity, 'halted');
                return;

            default:
                Logger::info('Unhandled Razorpay webhook event', ['event' => $event]);
                return;
        }
    }

    public function handlePaymentCapturedWebhook(array $paymentEntity): void {
        $paymentId = (string)($paymentEntity['id'] ?? '');
        if ($paymentId === '') {
            return;
        }

        $orderId = (string)($paymentEntity['order_id'] ?? '');
        $gatewaySubscriptionId = (string)($paymentEntity['subscription_id'] ?? '');
        $amount = SaaSBillingHelper::money(((float)($paymentEntity['amount'] ?? 0)) / 100);

        $local = $this->subscriptionModel->findByGatewayIds($orderId ?: null, $gatewaySubscriptionId ?: null);
        if (!$local) {
            Logger::security('Webhook payment captured with no local subscription', [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'gateway_subscription_id' => $gatewaySubscriptionId,
            ]);
            return;
        }

        $this->finalizePaymentSuccess(
            (int)$local['id'],
            $paymentId,
            $orderId !== '' ? $orderId : null,
            $gatewaySubscriptionId !== '' ? $gatewaySubscriptionId : null,
            $amount,
            'webhook'
        );
    }

    public function handleSubscriptionChargedWebhook(array $subscriptionEntity, array $paymentEntity): void {
        $gatewaySubscriptionId = (string)($subscriptionEntity['id'] ?? $paymentEntity['subscription_id'] ?? '');
        $orderId = (string)($paymentEntity['order_id'] ?? '');
        $paymentId = (string)($paymentEntity['id'] ?? '');
        if ($gatewaySubscriptionId === '' || $paymentId === '') {
            return;
        }

        $amount = SaaSBillingHelper::money(((float)($paymentEntity['amount'] ?? 0)) / 100);
        $local = $this->subscriptionModel->findByGatewayIds($orderId ?: null, $gatewaySubscriptionId);
        if (!$local) {
            Logger::warning('subscription.charged webhook with missing local subscription', [
                'gateway_subscription_id' => $gatewaySubscriptionId,
                'payment_id' => $paymentId,
            ]);
            return;
        }

        $this->finalizePaymentSuccess(
            (int)$local['id'],
            $paymentId,
            $orderId !== '' ? $orderId : null,
            $gatewaySubscriptionId,
            $amount,
            'webhook'
        );
    }

    public function handleSubscriptionStatusWebhook(array $subscriptionEntity, string $status): void {
        $gatewaySubscriptionId = (string)($subscriptionEntity['id'] ?? '');
        if ($gatewaySubscriptionId === '') {
            return;
        }

        $this->subscriptionModel->updateStatusByGatewaySubscription($gatewaySubscriptionId, $status);
        $local = $this->subscriptionModel->findByGatewayIds(null, $gatewaySubscriptionId);
        if (!$local) {
            return;
        }

        if (in_array($status, ['cancelled', 'halted', 'completed'], true)) {
            $this->db->query(
                "UPDATE companies SET subscription_status = ?, updated_at = ? WHERE id = ?",
                ['inactive', SaaSBillingHelper::now(), (int)$local['company_id']]
            );
            return;
        }

        if ($status === 'active') {
            $this->db->query(
                "UPDATE companies SET subscription_status = 'active', updated_at = ? WHERE id = ?",
                [SaaSBillingHelper::now(), (int)$local['company_id']]
            );
        }
    }

    private function applyPostPaymentEffects(array $subscription): void {
        if (!empty($subscription['promo_code_id'])) {
            $this->promoModel->registerUsage(
                (int)$subscription['promo_code_id'],
                (int)$subscription['company_id'],
                (int)$subscription['id'],
                (float)$subscription['discount_amount'],
                (float)$subscription['amount']
            );
        }

        $this->referralModel->markSuccessfulAfterPayment(
            (int)$subscription['company_id'],
            (int)$subscription['id'],
            (float)$subscription['amount']
        );
    }
}
