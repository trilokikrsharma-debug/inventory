<?php
/**
 * Tenant Subscription Model (platform scoped)
 */
class TenantSubscription extends Model {
    protected $table = 'tenant_subscriptions';
    protected $tenantScoped = false;
    protected $softDelete = false;
    private ?bool $requiresSubscriptionIdSeed = null;
    private ?array $companyColumns = null;

    /**
     * Get latest subscription for company.
     */
    public function latestForCompany(int $companyId): ?array {
        $row = $this->db->query(
            "SELECT ts.*, sp.name AS plan_name
             FROM tenant_subscriptions ts
             LEFT JOIN saas_plans sp ON sp.id = ts.plan_id
             WHERE ts.company_id = ?
             ORDER BY
                CASE
                    WHEN ts.status = 'active' THEN 0
                    WHEN ts.status = 'trial' THEN 1
                    WHEN ts.status = 'pending' THEN 2
                    ELSE 3
                END,
                ts.id DESC
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
     * Return the latest billable subscription window for a company.
     * The row is enriched with an effective expiry timestamp and flags.
     */
    public function latestBillableWindow(int $companyId): ?array {
        $row = $this->db->query(
            "SELECT ts.*, sp.name AS plan_name, sp.slug AS plan_slug, sp.billing_type, sp.duration_days
             FROM tenant_subscriptions ts
             LEFT JOIN saas_plans sp ON sp.id = ts.plan_id
             WHERE ts.company_id = ?
               AND ts.payment_status = 'paid'
             ORDER BY
                CASE
                    WHEN ts.status = 'active' THEN 0
                    WHEN ts.status = 'trial' THEN 1
                    WHEN ts.status = 'pending' THEN 2
                    WHEN ts.status = 'halted' THEN 3
                    WHEN ts.status = 'cancelled' THEN 4
                    ELSE 5
                END,
                COALESCE(ts.expires_at, ts.current_end, ts.started_at, ts.created_at) DESC,
                ts.id DESC
             LIMIT 1",
            [$companyId]
        )->fetch();

        if (!$row) {
            return null;
        }

        return $this->decorateLifecycleRow($row);
    }

    /**
     * Return the current active billing window if it is still valid.
     */
    public function currentActiveWindow(int $companyId): ?array {
        $row = $this->latestBillableWindow($companyId);
        if (!$row || !empty($row['is_expired'])) {
            return null;
        }

        return $row;
    }

    /**
     * Evaluate and optionally persist the tenant lifecycle state.
     *
     * Return structure:
     *  - status: active|trial|expired|inactive|suspended
     *  - is_active / is_trial / is_expired / is_manually_blocked
     *  - company / subscription / current_window
     */
    public function syncLifecycleState(int $companyId, bool $persist = true): array {
        $company = $this->db->query(
            "SELECT * FROM companies WHERE id = ? LIMIT 1",
            [$companyId]
        )->fetch();

        if (!$company) {
            return [
                'status' => 'missing',
                'is_active' => false,
                'is_trial' => false,
                'is_expired' => true,
                'is_manually_blocked' => false,
                'company' => null,
                'subscription' => null,
                'current_window' => null,
            ];
        }

        $currentStatus = strtolower(trim((string)($company['subscription_status'] ?? 'inactive')));
        $manualBlockStatuses = ['suspended'];
        $isManuallyBlocked = in_array($currentStatus, $manualBlockStatuses, true);

        $nowTs = time();
        $trialEndsAt = $this->parseTimestamp($company['trial_ends_at'] ?? null);
        $trialActive = $currentStatus === 'trial' && $trialEndsAt !== null && $trialEndsAt >= $nowTs;

        $window = $this->latestBillableWindow($companyId);
        $windowActive = $window && !empty($window['is_active_window']);
        $windowExpired = $window && !empty($window['is_expired']);

        $isActive = $windowActive || $currentStatus === 'active';
        $isTrial = $trialActive;
        $isExpired = false;
        $targetStatus = $currentStatus;

        if ($currentStatus === 'trial') {
            if ($windowActive) {
                $targetStatus = 'active';
                $isActive = true;
                $isTrial = false;
            } elseif ($trialActive) {
                $targetStatus = 'trial';
                $isTrial = true;
                $isActive = false;
            } else {
                $targetStatus = 'expired';
                $isExpired = true;
            }
        } elseif ($currentStatus === 'active') {
            if ($windowActive) {
                $targetStatus = 'active';
                $isActive = true;
            } else {
                $targetStatus = 'expired';
                $isExpired = true;
            }
        } elseif ($currentStatus === 'expired') {
            if ($windowActive) {
                $targetStatus = 'active';
                $isActive = true;
                $isExpired = false;
            } elseif ($trialActive) {
                $targetStatus = 'trial';
                $isTrial = true;
                $isExpired = false;
            } else {
                $isExpired = true;
            }
        } elseif (!$isManuallyBlocked && $windowActive) {
            $targetStatus = 'active';
            $isActive = true;
        } elseif (!$isManuallyBlocked && $trialActive) {
            $targetStatus = 'trial';
            $isTrial = true;
        } else {
            $isExpired = $windowExpired || ($trialEndsAt !== null && $trialEndsAt < $nowTs);
        }

        $subscription = $window;

        if ($persist) {
            $this->persistLifecycleState($company, $subscription, $targetStatus, $isActive, $isTrial, $isExpired, $isManuallyBlocked, $companyId);
            $company['subscription_status'] = $targetStatus;
        }

        return [
            'status' => $targetStatus,
            'is_active' => $isActive && !$isExpired && !$isManuallyBlocked,
            'is_trial' => $isTrial && !$isExpired && !$isManuallyBlocked,
            'is_expired' => $isExpired || $windowExpired || (!$isManuallyBlocked && !$windowActive && !$trialActive && $targetStatus !== 'active' && $targetStatus !== 'trial'),
            'is_manually_blocked' => $isManuallyBlocked,
            'company' => $company,
            'subscription' => $subscription,
            'current_window' => $window,
        ];
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
            $razorpaySubscriptionSeed = $this->subscriptionIdSeedValue();

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
                (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NULL, NULL, ?, ?)",
                [
                    $companyId,
                    $razorpaySubscriptionSeed,
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

            $publicMessage = 'Failed to create checkout session.';
            if (stripos($e->getMessage(), 'razorpay_subscription_id') !== false
                && stripos($e->getMessage(), 'cannot be null') !== false) {
                $publicMessage = 'Billing schema mismatch detected. Please run latest database migrations.';
            }

            return [
                'success' => false,
                'message' => $publicMessage,
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
                 WHERE id = ? AND company_id = ?",
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
                    (int)$sub['company_id'],
                ]
            );

            // Ensure this plan becomes current company plan + keep legacy fields aligned.
            $planMeta = $db->query(
                "SELECT id, name, slug, max_users FROM saas_plans WHERE id = ? LIMIT 1",
                [(int)$sub['plan_id']]
            )->fetch() ?: [];
            $legacyPlanAlias = $this->legacyPlanAliasFromPlan($planMeta);

            $companySet = [
                "saas_plan_id = ?",
                "subscription_status = 'active'",
                "updated_at = ?",
            ];
            $companyParams = [
                (int)$sub['plan_id'],
                SaaSBillingHelper::now(),
            ];

            if ($this->companyHasColumn('plan')) {
                $companySet[] = "plan = ?";
                $companyParams[] = $legacyPlanAlias;
            }

            if ($this->companyHasColumn('max_users')) {
                $planMaxUsers = (int)($planMeta['max_users'] ?? 0);
                if ($planMaxUsers > 0) {
                    $companySet[] = "max_users = ?";
                    $companyParams[] = $planMaxUsers;
                }
            }

            $companyParams[] = (int)$sub['company_id'];
            $db->query(
                "UPDATE companies
                 SET " . implode(', ', $companySet) . "
                 WHERE id = ?",
                $companyParams
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
                "SELECT * FROM tenant_subscriptions WHERE id = ? AND company_id = ? LIMIT 1",
                [$subscriptionId, (int)$sub['company_id']]
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
                 WHERE id = ? AND company_id = ?",
                [
                    SaaSBillingHelper::now(),
                    $subscriptionId,
                    (int)$sub['company_id'],
                ]
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
                 WHERE id = ? AND company_id = ?",
                [SaaSBillingHelper::now(), SaaSBillingHelper::now(), $subscriptionId, $companyId]
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

    public function updateStatusByGatewaySubscription(string $gatewaySubscriptionId, string $status, ?int $companyId = null): void {
        $sql = "UPDATE tenant_subscriptions
                SET status = ?, updated_at = ?
                WHERE razorpay_subscription_id = ?";
        $params = [$status, SaaSBillingHelper::now(), $gatewaySubscriptionId];

        if ($companyId !== null) {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }

        $this->db->query($sql, $params);
    }

    /**
     * Build lifecycle flags for a subscription row.
     */
    private function decorateLifecycleRow(array $row): array {
        $effectiveExpiresAt = $row['expires_at'] ?? $row['current_end'] ?? $row['last_payment_at'] ?? $row['started_at'] ?? $row['created_at'] ?? null;
        $effectiveExpiresTs = $this->parseTimestamp($effectiveExpiresAt);
        $status = strtolower(trim((string)($row['status'] ?? '')));
        $nowTs = time();

        $row['effective_expires_at'] = $effectiveExpiresAt;
        $row['effective_expires_ts'] = $effectiveExpiresTs;
        $row['is_expired'] = $effectiveExpiresTs !== null ? $effectiveExpiresTs < $nowTs : false;
        $row['is_active_window'] = $effectiveExpiresTs !== null
            && $effectiveExpiresTs >= $nowTs
            && in_array($status, ['active', 'trial'], true);

        return $row;
    }

    /**
     * Parse a datetime value into a unix timestamp.
     */
    private function parseTimestamp($value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $ts = strtotime((string)$value);
        return $ts === false ? null : $ts;
    }

    /**
     * Persist lifecycle changes in an idempotent, tenant-safe way.
     */
    private function persistLifecycleState(
        array $company,
        ?array $subscription,
        string $targetStatus,
        bool $isActive,
        bool $isTrial,
        bool $isExpired,
        bool $isManuallyBlocked,
        int $companyId
    ): void {
        if ($isManuallyBlocked) {
            return;
        }

        $currentStatus = strtolower(trim((string)($company['subscription_status'] ?? 'inactive')));
        $companyNeedsUpdate = $currentStatus !== $targetStatus;
        $subscriptionNeedsUpdate = false;
        $subscriptionId = $subscription['id'] ?? null;

        if ($targetStatus === 'expired' && $subscription && !in_array(strtolower((string)($subscription['status'] ?? '')), ['halted', 'cancelled'], true)) {
            $subscriptionNeedsUpdate = true;
        }

        if (!$companyNeedsUpdate && !$subscriptionNeedsUpdate) {
            return;
        }

        $db = $this->db;
        $db->beginTransaction();

        try {
            if ($companyNeedsUpdate) {
                $db->query(
                    "UPDATE companies
                     SET subscription_status = ?, updated_at = ?
                     WHERE id = ?",
                    [$targetStatus, SaaSBillingHelper::now(), $companyId]
                );
            }

            if ($subscriptionNeedsUpdate && $subscriptionId !== null) {
                $db->query(
                    "UPDATE tenant_subscriptions
                     SET status = 'halted', updated_at = ?
                     WHERE id = ? AND company_id = ?",
                    [SaaSBillingHelper::now(), (int)$subscriptionId, $companyId]
                );
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            Logger::warning('Failed to sync subscription lifecycle state', [
                'company_id' => $companyId,
                'target_status' => $targetStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Legacy schema compatibility:
     * old tenant_subscriptions schemas had razorpay_subscription_id as NOT NULL without a default.
     * For order-mode checkout, we must seed an empty string instead of NULL.
     */
    private function subscriptionIdSeedValue(): ?string {
        return $this->requiresSubscriptionIdSeedValue() ? '' : null;
    }

    private function requiresSubscriptionIdSeedValue(): bool {
        if ($this->requiresSubscriptionIdSeed !== null) {
            return $this->requiresSubscriptionIdSeed;
        }

        try {
            $column = $this->db->query(
                "SELECT IS_NULLABLE, COLUMN_DEFAULT
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = 'razorpay_subscription_id'
                 LIMIT 1",
                [$this->table]
            )->fetch();

            if (!$column) {
                $this->requiresSubscriptionIdSeed = false;
                return false;
            }

            $isNullable = strtoupper((string)($column['IS_NULLABLE'] ?? 'YES')) === 'YES';
            $hasDefault = array_key_exists('COLUMN_DEFAULT', $column) && $column['COLUMN_DEFAULT'] !== null;
            $this->requiresSubscriptionIdSeed = !$isNullable && !$hasDefault;
            return $this->requiresSubscriptionIdSeed;
        } catch (\Throwable $e) {
            $this->requiresSubscriptionIdSeed = false;
            return false;
        }
    }

    private function companyHasColumn(string $column): bool {
        if ($this->companyColumns === null) {
            $this->companyColumns = [];
            try {
                $rows = $this->db->query(
                    "SELECT COLUMN_NAME
                     FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'companies'"
                )->fetchAll();
                foreach ($rows as $row) {
                    $name = (string)($row['COLUMN_NAME'] ?? '');
                    if ($name !== '') {
                        $this->companyColumns[$name] = true;
                    }
                }
            } catch (\Throwable $e) {
                // Fail-open: avoid breaking payment success in restricted environments.
                $this->companyColumns = [];
            }
        }

        return !empty($this->companyColumns[$column]);
    }

    private function legacyPlanAliasFromPlan(array $plan): string {
        $slug = strtolower(trim((string)($plan['slug'] ?? '')));
        $name = strtolower(trim((string)($plan['name'] ?? '')));
        $candidate = $slug !== '' ? $slug : $name;

        if ($candidate === '') {
            return 'starter';
        }

        if (strpos($candidate, 'enterprise') !== false || $candidate === 'pro' || $candidate === 'premium') {
            return 'pro';
        }

        if (strpos($candidate, 'professional') !== false || strpos($candidate, 'growth') !== false || $candidate === 'business') {
            return 'growth';
        }

        return 'starter';
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
