<?php
/**
 * Promo Code Model (platform scoped)
 */
class PromoCode extends Model {
    protected $table = 'promo_codes';
    protected $tenantScoped = false;
    protected $softDelete = false;

    public function listForAdmin(): array {
        return $this->db->query(
            "SELECT * FROM {$this->table} ORDER BY id DESC"
        )->fetchAll();
    }

    public function findByCode(string $code): ?array {
        $row = $this->db->query(
            "SELECT * FROM {$this->table} WHERE code = ? LIMIT 1",
            [strtoupper(trim($code))]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Validate create/edit payload.
     */
    public function validatePayload(array $input, ?int $editingId = null): array {
        $errors = [];

        $code = strtoupper(trim((string)($input['code'] ?? '')));
        $title = trim((string)($input['title'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $discountType = trim((string)($input['discount_type'] ?? 'fixed'));
        $discountValue = SaaSBillingHelper::money($input['discount_value'] ?? 0);
        $maxDiscountAmount = SaaSBillingHelper::money($input['max_discount_amount'] ?? 0);
        $minimumAmount = SaaSBillingHelper::money($input['minimum_amount'] ?? 0);
        $usageLimitTotal = max(0, (int)($input['usage_limit_total'] ?? 0));
        $usageLimitPerCompany = max(0, (int)($input['usage_limit_per_company'] ?? 0));
        $validFrom = trim((string)($input['valid_from'] ?? ''));
        $validTo = trim((string)($input['valid_to'] ?? ''));
        $newCustomersOnly = !empty($input['new_customers_only']) ? 1 : 0;
        $allowBelowOne = !empty($input['allow_below_one']) ? 1 : 0;
        $status = !empty($input['status']) && strtolower((string)$input['status']) === 'inactive'
            ? 'inactive'
            : 'active';
        $planIds = SaaSBillingHelper::parsePlanIds($input['applicable_plan_ids'] ?? '');

        if ($code === '' || !preg_match('/^[A-Z0-9_-]{3,30}$/', $code)) {
            $errors[] = 'Promo code must be 3-30 chars (A-Z, 0-9, underscore, dash).';
        }

        if ($title === '' || mb_strlen($title) > 120) {
            $errors[] = 'Title is required and must be under 120 characters.';
        }

        if (!in_array($discountType, ['fixed', 'percentage'], true)) {
            $errors[] = 'Discount type must be fixed or percentage.';
        }

        if ($discountValue <= 0) {
            $errors[] = 'Discount value must be greater than 0.';
        }

        if ($discountType === 'percentage' && $discountValue > 100) {
            $errors[] = 'Percentage discount cannot exceed 100%.';
        }

        if ($minimumAmount < 0) {
            $errors[] = 'Minimum amount cannot be negative.';
        }

        if ($validFrom !== '' && !SaaSBillingHelper::validDate($validFrom)) {
            $errors[] = 'Valid from must be a valid date (YYYY-MM-DD).';
        }
        if ($validTo !== '' && !SaaSBillingHelper::validDate($validTo)) {
            $errors[] = 'Valid to must be a valid date (YYYY-MM-DD).';
        }
        if ($validFrom !== '' && $validTo !== '' && strtotime($validTo) < strtotime($validFrom)) {
            $errors[] = 'Valid to cannot be earlier than valid from.';
        }

        $existsSql = "SELECT COUNT(*) FROM {$this->table} WHERE code = ?";
        $existsParams = [$code];
        if ($editingId !== null) {
            $existsSql .= " AND id != ?";
            $existsParams[] = $editingId;
        }
        if ((int)$this->db->query($existsSql, $existsParams)->fetchColumn() > 0) {
            $errors[] = 'Promo code already exists.';
        }

        $payload = [
            'code' => $code,
            'title' => $title,
            'description' => $description,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'max_discount_amount' => $maxDiscountAmount > 0 ? $maxDiscountAmount : null,
            'minimum_amount' => $minimumAmount,
            'usage_limit_total' => $usageLimitTotal,
            'usage_limit_per_company' => $usageLimitPerCompany,
            'valid_from' => $validFrom !== '' ? ($validFrom . ' 00:00:00') : null,
            'valid_to' => $validTo !== '' ? ($validTo . ' 23:59:59') : null,
            'applicable_plan_ids' => !empty($planIds) ? json_encode($planIds) : null,
            'new_customers_only' => $newCustomersOnly,
            'allow_below_one' => $allowBelowOne,
            'status' => $status,
            'updated_at' => SaaSBillingHelper::now(),
        ];

        if ($editingId === null) {
            $payload['used_count'] = 0;
            $payload['created_at'] = SaaSBillingHelper::now();
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'payload' => $payload,
        ];
    }

    public function createPromo(array $input): array {
        $validated = $this->validatePayload($input);
        if (!$validated['ok']) {
            return ['success' => false, 'errors' => $validated['errors']];
        }
        $id = $this->create($validated['payload']);
        return ['success' => true, 'id' => (int)$id];
    }

    public function updatePromo(int $id, array $input): array {
        $promo = $this->find($id);
        if (!$promo) {
            return ['success' => false, 'errors' => ['Promo not found.']];
        }
        $validated = $this->validatePayload($input, $id);
        if (!$validated['ok']) {
            return ['success' => false, 'errors' => $validated['errors']];
        }
        $this->update($id, $validated['payload']);
        return ['success' => true, 'id' => $id];
    }

    public function deletePromo(int $id): array {
        $promo = $this->find($id);
        if (!$promo) {
            return ['success' => false, 'message' => 'Promo not found.'];
        }

        $used = (int)$this->db->query(
            "SELECT COUNT(*) FROM promo_code_usages WHERE promo_code_id = ?",
            [$id]
        )->fetchColumn();

        if ($used > 0) {
            $this->update($id, ['status' => 'inactive', 'updated_at' => SaaSBillingHelper::now()]);
            return [
                'success' => true,
                'message' => 'Promo had usage history and was disabled instead of deleted.',
            ];
        }

        $this->hardDelete($id);
        return ['success' => true, 'message' => 'Promo deleted successfully.'];
    }

    /**
     * Validate promo for checkout (server-trusted).
     */
    public function validateForCheckout(string $code, int $companyId, array $plan, float $baseAmount): array {
        $promo = $this->findByCode($code);
        if (!$promo) {
            return ['success' => false, 'message' => 'Promo code not found.'];
        }

        if (($promo['status'] ?? 'inactive') !== 'active') {
            return ['success' => false, 'message' => 'Promo code is inactive.'];
        }

        $now = date('Y-m-d H:i:s');
        if (!empty($promo['valid_from']) && $now < $promo['valid_from']) {
            return ['success' => false, 'message' => 'Promo code is not active yet.'];
        }
        if (!empty($promo['valid_to']) && $now > $promo['valid_to']) {
            return ['success' => false, 'message' => 'Promo code has expired.'];
        }

        $minimumAmount = SaaSBillingHelper::money($promo['minimum_amount'] ?? 0);
        if ($baseAmount < $minimumAmount) {
            return ['success' => false, 'message' => 'Minimum purchase amount for this promo is not met.'];
        }

        if (!SaaSBillingHelper::isPromoApplicableToPlan($promo, (int)$plan['id'])) {
            return ['success' => false, 'message' => 'Promo code is not applicable for this plan.'];
        }

        if ((int)($promo['usage_limit_total'] ?? 0) > 0) {
            if ((int)($promo['used_count'] ?? 0) >= (int)$promo['usage_limit_total']) {
                return ['success' => false, 'message' => 'Promo code usage limit has been reached.'];
            }
        }

        if ((int)($promo['usage_limit_per_company'] ?? 0) > 0) {
            $usedByCompany = (int)$this->db->query(
                "SELECT COUNT(*) FROM promo_code_usages WHERE promo_code_id = ? AND company_id = ?",
                [(int)$promo['id'], $companyId]
            )->fetchColumn();
            if ($usedByCompany >= (int)$promo['usage_limit_per_company']) {
                return ['success' => false, 'message' => 'You have already used this promo code the maximum allowed times.'];
            }
        }

        if (!empty($promo['new_customers_only'])) {
            $paidCount = (int)$this->db->query(
                "SELECT COUNT(*) FROM tenant_subscriptions WHERE company_id = ? AND payment_status = 'paid'",
                [$companyId]
            )->fetchColumn();
            if ($paidCount > 0) {
                return ['success' => false, 'message' => 'This promo is only valid for new customers.'];
            }
        }

        $discount = SaaSBillingHelper::discountAmount(
            $baseAmount,
            (string)$promo['discount_type'],
            SaaSBillingHelper::money($promo['discount_value'] ?? 0),
            isset($promo['max_discount_amount']) ? SaaSBillingHelper::money($promo['max_discount_amount']) : null
        );

        $allowBelowOne = !empty($promo['allow_below_one']);
        $final = SaaSBillingHelper::finalPayable($baseAmount, $discount, $allowBelowOne);

        // If floor changed final amount, adjust discount accordingly.
        $discount = SaaSBillingHelper::money(max(0, $baseAmount - $final));

        return [
            'success' => true,
            'promo' => $promo,
            'discount_amount' => $discount,
            'final_amount' => $final,
            'message' => 'Promo applied successfully.',
        ];
    }

    /**
     * Idempotent promo usage record after successful payment.
     */
    public function registerUsage(
        int $promoCodeId,
        int $companyId,
        int $subscriptionId,
        float $discountAmount,
        float $finalAmount
    ): bool {
        $db = $this->db;
        $db->beginTransaction();

        try {
            $exists = $db->query(
                "SELECT id FROM promo_code_usages
                 WHERE promo_code_id = ? AND company_id = ? AND subscription_id = ?
                 LIMIT 1",
                [$promoCodeId, $companyId, $subscriptionId]
            )->fetch();

            if (!$exists) {
                $db->query(
                    "INSERT INTO promo_code_usages
                    (promo_code_id, company_id, subscription_id, discount_amount, final_amount, used_at)
                    VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $promoCodeId,
                        $companyId,
                        $subscriptionId,
                        SaaSBillingHelper::money($discountAmount),
                        SaaSBillingHelper::money($finalAmount),
                        SaaSBillingHelper::now(),
                    ]
                );
            }

            // Keep aggregate counter accurate.
            $total = (int)$db->query(
                "SELECT COUNT(*) FROM promo_code_usages WHERE promo_code_id = ?",
                [$promoCodeId]
            )->fetchColumn();

            $db->query(
                "UPDATE promo_codes SET used_count = ?, updated_at = ? WHERE id = ?",
                [$total, SaaSBillingHelper::now(), $promoCodeId]
            );

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollback();
            Logger::security('Promo usage registration failed', [
                'promo_code_id' => $promoCodeId,
                'company_id' => $companyId,
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

