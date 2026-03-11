<?php
/**
 * Tenant Subscription Model (platform scoped)
 */
class TenantSubscription extends Model {
    protected $table = 'tenant_subscriptions';
    protected $tenantScoped = false;
    protected $softDelete = false;

    /**
     * Get latest subscription for company.
     */
    public function latestForCompany(int $companyId): ?array {
        $row = $this->db->query(
            "SELECT ts.*, sp.name AS plan_name
             FROM tenant_subscriptions ts
             LEFT JOIN saas_plans sp ON sp.id = ts.plan_id
             WHERE ts.company_id = ?
             ORDER BY ts.id DESC
             LIMIT 1",
            [$companyId]
        )->fetch();

        return $row ?: null;
    }

    /**
     * List subscriptions for platform UI.
     */
    public function listForPlatform(string $status = ''): array {
        $where = [];
        $params = [];
        if ($status !== '') {
            $where[] = "ts.status = ?";
            $params[] = $status;
        }

        $sql = "SELECT ts.*,
                       c.name AS company_name,
                       sp.name AS plan_name
                FROM tenant_subscriptions ts
                JOIN companies c ON c.id = ts.company_id
                JOIN saas_plans sp ON sp.id = ts.plan_id";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY ts.id DESC";

        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * List payment logs with tenant + plan context.
     */
    public function listPaymentLogs(int $limit = 100, bool $failedOnly = false): array {
        $where = [];
        if ($failedOnly) {
            $where[] = "pt.status IN ('failed', 'error')";
        }

        $sql = "SELECT pt.*,
                       c.name AS company_name,
                       sp.name AS plan_name
                FROM saas_payment_transactions pt
                LEFT JOIN tenant_subscriptions ts ON ts.id = pt.subscription_id
                LEFT JOIN companies c ON c.id = pt.company_id
                LEFT JOIN saas_plans sp ON sp.id = ts.plan_id";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY pt.id DESC LIMIT " . max(1, min(500, $limit));

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Create pending local subscription and payment attempt.
     */
    public function createPendingCheckout(
        int $companyId,
        array $plan,
        float $originalAmount,
        float $discountAmount,
        float $finalAmount,
        ?array $promo = null
    ): array {
        $db = $this->db;
        $db->beginTransaction();

        try {
            $latest = $this->latestForCompany($companyId);
            $changeType = 'new';
            if ($latest && in_array($latest['status'], ['active', 'pending', 'trial'], true)) {
                $changeType = ((int)$latest['plan_id'] === (int)$plan['id']) ? 'renewal' : 'upgrade';
            }

            $orderCode = SaaSBillingHelper::generateOrderCode('SUB');
            $idempotencyKey = hash(
                'sha256',
                $companyId . '|' . $plan['id'] . '|' . $orderCode . '|' . microtime(true) . '|' . random_int(1000, 9999)
            );

            $durationDays = max(1, (int)($plan['duration_days'] ?? 30));
            $subscriptionType = in_array((string)$plan['billing_type'], ['monthly', 'yearly'], true)
                ? 'recurring'
                : 'one_time';

            $db->query(
                "INSERT INTO tenant_subscriptions
                (
                    company_id, razorpay_subscription_id, plan_id, status,
                    subscription_type, order_code, change_type,
                    amount, original_amount, discount_amount, promo_code_id, promo_code,
                    payment_status, duration_days, idempotency_key,
                    current_start, current_end, created_at, updated_at
                )
                VALUES
                (?, NULL, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NULL, NULL, ?, ?)",
                [
                    $companyId,
                    (int)$plan['id'],
                    $subscriptionType,
                    $orderCode,
                    $changeType,
                    SaaSBillingHelper::money($finalAmount),
                    SaaSBillingHelper::money($originalAmount),
                    SaaSBillingHelper::money($discountAmount),
                    $promo ? (int)$promo['id'] : null,
                    $promo ? (string)$promo['code'] : null,
                    $durationDays,
                    $idempotencyKey,
                    SaaSBillingHelper::now(),
                    SaaSBillingHelper::now(),
                ]
            );
            $subscriptionId = (int)$db->lastInsertId();

            $db->query(
                "INSERT INTO saas_payment_transactions
                (
                    subscription_id, company_id, amount, currency, status,
                    gateway, idempotency_key, created_at, updated_at
                )
                VALUES (?, ?, ?, 'INR', 'created', 'razorpay', ?, ?, ?)",
                [
                    $subscriptionId,
                    $companyId,
                    SaaSBillingHelper::money($finalAmount),
                    $idempotencyKey,
                    SaaSBillingHelper::now(),
                    SaaSBillingHelper::now(),
                ]
            );

            $paymentTxnId = (int)$db->lastInsertId();
            $db->commit();

            return [
                'success' => true,
                'subscription_id' => $subscriptionId,
                'payment_txn_id' => $paymentTxnId,
                'order_code' => $orderCode,
                'idempotency_key' => $idempotencyKey,
                'change_type' => $changeType,
                'subscription_type' => $subscriptionType,
            ];
        } catch (\Throwable $e) {
            $db->rollback();
            Logger::error('Failed to create pending checkout', [
                'company_id' => $companyId,
                'plan_id' => $plan['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => 'Failed to create checkout session.',
            ];
        }
    }

    /**
     * Attach razorpay ids after order/subscription creation.
     */
    public function attachGatewayReference(
        int $subscriptionId,
        ?string $razorpayOrderId,
        ?string $razorpaySubscriptionId,
        ?string $gatewayMode = null
    ): void {
        $this->db->query(
            "UPDATE tenant_subscriptions
             SET razorpay_order_id = ?, razorpay_subscription_id = ?,
                 gateway_mode = ?, updated_at = ?
             WHERE id = ?",
            [
                $razorpayOrderId,
                $razorpaySubscriptionId,
                $gatewayMode,
                SaaSBillingHelper::now(),
                $subscriptionId,
            ]
        );

        $this->db->query(
            "UPDATE saas_payment_transactions
             SET razorpay_order_id = COALESCE(?, razorpay_order_id),
                 razorpay_subscription_id = COALESCE(?, razorpay_subscription_id),
                 updated_at = ?
             WHERE subscription_id = ?",
            [
                $razorpayOrderId,
                $razorpaySubscriptionId,
                SaaSBillingHelper::now(),
                $subscriptionId,
            ]
        );
    }

    /**
     * Find subscription for company ownership checks.
     */
    public function findForCompany(int $subscriptionId, int $companyId): ?array {
        $row = $this->db->query(
            "SELECT * FROM tenant_subscriptions WHERE id = ? AND company_id = ? LIMIT 1",
            [$subscriptionId, $companyId]
        )->fetch();
        return $row ?: null;
    }

    public function findByGatewayIds(?string $orderId, ?string $subscriptionGatewayId): ?array {
        if ($orderId) {
            $row = $this->db->query(
                "SELECT * FROM tenant_subscriptions WHERE razorpay_order_id = ? LIMIT 1",
                [$orderId]
            )->fetch();
            if ($row) {
                return $row;
            }
        }

        if ($subscriptionGatewayId) {
            $row = $this->db->query(
                "SELECT * FROM tenant_subscriptions WHERE razorpay_subscription_id = ? ORDER BY id DESC LIMIT 1",
                [$subscriptionGatewayId]
            )->fetch();
            if ($row) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Idempotent success update.
     */
    public function markPaymentSuccess(
        int $subscriptionId,
        string $paymentId,
        ?string $orderId,
        ?string $razorpaySubscriptionId,
        float $capturedAmount,
        string $source = 'callback'
    ): array {
        $db = $this->db;
        $db->beginTransaction();

        try {
            $sub = $db->query(
                "SELECT * FROM tenant_subscriptions WHERE id = ? LIMIT 1 FOR UPDATE",
                [$subscriptionId]
            )->fetch();

            if (!$sub) {
                $db->rollback();
                return ['success' => false, 'message' => 'Subscription not found.'];
            }

            // Idempotent exit if already paid with same payment id.
            if (($sub['payment_status'] ?? '') === 'paid' && !empty($sub['razorpay_payment_id']) && $sub['razorpay_payment_id'] === $paymentId) {
                $db->commit();
                return ['success' => true, 'already_processed' => true, 'subscription' => $sub];
            }

            // Guard against mismatched amount tampering.
            $expected = SaaSBillingHelper::money($sub['amount'] ?? 0);
            $capturedAmount = SaaSBillingHelper::money($capturedAmount);
            if (abs($capturedAmount - $expected) > 0.01) {
                Logger::security('Captured amount mismatch for subscription', [
                    'subscription_id' => $subscriptionId,
                    'expected' => $expected,
                    'captured' => $capturedAmount,
                    'payment_id' => $paymentId,
                    'source' => $source,
                ]);
                $db->rollback();
                return ['success' => false, 'message' => 'Payment amount verification failed.'];
            }

            $start = date('Y-m-d H:i:s');
            $duration = max(1, (int)($sub['duration_days'] ?? 30));
            $end = date('Y-m-d H:i:s', strtotime($start . " +{$duration} days"));

            $db->query(
                "UPDATE tenant_subscriptions
                 SET status = 'active',
                     payment_status = 'paid',
                     razorpay_payment_id = ?,
                     razorpay_order_id = COALESCE(?, razorpay_order_id),
                     razorpay_subscription_id = COALESCE(?, razorpay_subscription_id),
                     current_start = ?,
                     current_end = ?,
                     started_at = COALESCE(started_at, ?),
                     expires_at = ?,
                     last_payment_at = ?,
                     updated_at = ?
                 WHERE id = ?",
                [
                    $paymentId,
                    $orderId,
                    $razorpaySubscriptionId,
                    $start,
                    $end,
                    $start,
                    $end,
                    $start,
                    SaaSBillingHelper::now(),
                    $subscriptionId,
                ]
            );

            // Ensure this plan becomes current company plan.
            $db->query(
                "UPDATE companies
                 SET saas_plan_id = ?, subscription_status = 'active', updated_at = ?
                 WHERE id = ?",
                [
                    (int)$sub['plan_id'],
                    SaaSBillingHelper::now(),
                    (int)$sub['company_id'],
                ]
            );

            // Mark older active subscription rows as upgraded/replaced.
            $db->query(
                "UPDATE tenant_subscriptions
                 SET status = CASE
                     WHEN status = 'active' THEN 'upgraded'
                     WHEN status = 'pending' THEN 'cancelled'
                     ELSE status
                 END,
                 updated_at = ?
                 WHERE company_id = ?
                   AND id != ?
                   AND status IN ('active', 'pending')",
                [
                    SaaSBillingHelper::now(),
                    (int)$sub['company_id'],
                    $subscriptionId,
                ]
            );

            $db->query(
                "UPDATE saas_payment_transactions
                 SET status = 'captured',
                     razorpay_payment_id = ?,
                     razorpay_order_id = COALESCE(?, razorpay_order_id),
                     razorpay_subscription_id = COALESCE(?, razorpay_subscription_id),
                     paid_at = ?,
                     source = ?,
                     updated_at = ?
                 WHERE subscription_id = ?",
                [
                    $paymentId,
                    $orderId,
                    $razorpaySubscriptionId,
                    $start,
                    $source,
                    SaaSBillingHelper::now(),
                    $subscriptionId,
                ]
            );

            $db->query(
                "INSERT INTO tenant_billing_history
                 (company_id, razorpay_payment_id, amount, currency, status, billing_date)
                 VALUES (?, ?, ?, 'INR', 'captured', ?)",
                [
                    (int)$sub['company_id'],
                    $paymentId,
                    $capturedAmount,
                    $start,
                ]
            );

            $updated = $db->query(
                "SELECT * FROM tenant_subscriptions WHERE id = ? LIMIT 1",
                [$subscriptionId]
            )->fetch();

            $db->commit();
            return [
                'success' => true,
                'already_processed' => false,
                'subscription' => $updated,
            ];
        } catch (\Throwable $e) {
            $db->rollback();
            Logger::error('Failed to mark payment success', [
                'subscription_id' => $subscriptionId,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Unable to update subscription after payment.'];
        }
    }

    public function markPaymentFailed(
        int $subscriptionId,
        string $reason,
        ?string $paymentId = null
    ): void {
        $this->db->beginTransaction();
        try {
            $sub = $this->db->query(
                "SELECT * FROM tenant_subscriptions WHERE id = ? LIMIT 1 FOR UPDATE",
                [$subscriptionId]
            )->fetch();

            if (!$sub) {
                $this->db->rollback();
                return;
            }

            if (($sub['payment_status'] ?? '') === 'paid') {
                $this->db->commit();
                return;
            }

            $this->db->query(
                "UPDATE tenant_subscriptions
                 SET status = 'failed', payment_status = 'failed', updated_at = ?
                 WHERE id = ?",
                [SaaSBillingHelper::now(), $subscriptionId]
            );

            $this->db->query(
                "UPDATE saas_payment_transactions
                 SET status = 'failed',
                     razorpay_payment_id = COALESCE(?, razorpay_payment_id),
                     failure_reason = ?,
                     updated_at = ?
                 WHERE subscription_id = ?",
                [
                    $paymentId,
                    substr($reason, 0, 500),
                    SaaSBillingHelper::now(),
                    $subscriptionId,
                ]
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            Logger::error('Failed to mark payment failed', [
                'subscription_id' => $subscriptionId,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancel active subscription locally (and optionally in gateway).
     */
    public function markCancelled(int $subscriptionId, int $companyId): bool {
        $sub = $this->findForCompany($subscriptionId, $companyId);
        if (!$sub) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->db->query(
                "UPDATE tenant_subscriptions
                 SET status = 'cancelled',
                     cancelled_at = ?,
                     updated_at = ?
                 WHERE id = ?",
                [SaaSBillingHelper::now(), SaaSBillingHelper::now(), $subscriptionId]
            );

            $this->db->query(
                "UPDATE companies
                 SET subscription_status = 'inactive', updated_at = ?
                 WHERE id = ?",
                [SaaSBillingHelper::now(), $companyId]
            );

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Idempotency guard for webhook processing.
     * Returns false when event key already exists.
     */
    public function registerWebhookEvent(string $eventKey, string $event, string $payload, string $signature): bool {
        try {
            $this->db->query(
                "INSERT INTO saas_webhook_events
                (event_key, event_name, payload, signature, process_status, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'received', ?, ?)",
                [
                    $eventKey,
                    $event,
                    $payload,
                    $signature,
                    SaaSBillingHelper::now(),
                    SaaSBillingHelper::now(),
                ]
            );
            return true;
        } catch (\Throwable $e) {
            // Duplicate event key => already handled or in progress.
            return false;
        }
    }

    public function markWebhookProcessed(string $eventKey, string $status = 'processed', ?string $error = null): void {
        $this->db->query(
            "UPDATE saas_webhook_events
             SET process_status = ?, error_message = ?, processed_at = ?, updated_at = ?
             WHERE event_key = ?",
            [
                $status,
                $error ? substr($error, 0, 500) : null,
                SaaSBillingHelper::now(),
                SaaSBillingHelper::now(),
                $eventKey,
            ]
        );
    }

    public function updateStatusByGatewaySubscription(string $gatewaySubscriptionId, string $status): void {
        $this->db->query(
            "UPDATE tenant_subscriptions
             SET status = ?, updated_at = ?
             WHERE razorpay_subscription_id = ?",
            [$status, SaaSBillingHelper::now(), $gatewaySubscriptionId]
        );
    }

    public function dashboardMetrics(): array {
        $active = (int)$this->db->query(
            "SELECT COUNT(*) FROM tenant_subscriptions WHERE status = 'active'"
        )->fetchColumn();

        $revenue = (float)$this->db->query(
            "SELECT COALESCE(SUM(amount), 0) FROM saas_payment_transactions WHERE status = 'captured'"
        )->fetchColumn();

        $planWise = $this->db->query(
            "SELECT sp.name, COUNT(*) AS subscribers
             FROM tenant_subscriptions ts
             JOIN saas_plans sp ON sp.id = ts.plan_id
             WHERE ts.status = 'active'
             GROUP BY sp.id, sp.name
             ORDER BY subscribers DESC"
        )->fetchAll();

        return [
            'active_subscriptions' => $active,
            'total_revenue' => SaaSBillingHelper::money($revenue),
            'plan_wise_subscribers' => $planWise,
        ];
    }
}

