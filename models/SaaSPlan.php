<?php
/**
 * SaaS Plan Model (platform scoped)
 */
class SaaSPlan extends Model {
    protected $table = 'saas_plans';
    protected $tenantScoped = false;
    protected $softDelete = false;
    private static bool $schemaChecked = false;

    public function __construct() {
        parent::__construct();
        $this->ensureSchema();
    }

    /**
     * List all plans for super admin.
     */
    public function listForAdmin(): array {
        $this->ensureSchema();

        return $this->db->query(
            "SELECT * FROM {$this->table} ORDER BY sort_order ASC, id ASC"
        )->fetchAll();
    }

    /**
     * List active plans for tenant checkout.
     */
    public function listForCheckout(): array {
        $this->ensureSchema();

        return $this->db->query(
            "SELECT * FROM {$this->table}
             WHERE status = 'active'
             ORDER BY is_featured DESC, sort_order ASC, id ASC"
        )->fetchAll();
    }

    /**
     * Find active plan by id.
     */
    public function findActive(int $id): ?array {
        $this->ensureSchema();

        $row = $this->db->query(
            "SELECT * FROM {$this->table} WHERE id = ? AND status = 'active' LIMIT 1",
            [$id]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Validate and normalize payload.
     */
    public function validatePayload(array $input, ?int $editingId = null): array {
        $errors = [];

        $name = trim((string)($input['name'] ?? ''));
        $slugInput = trim((string)($input['slug'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $price = SaaSBillingHelper::money($input['price'] ?? 0);
        $offer = SaaSBillingHelper::money($input['offer_price'] ?? 0);
        $billingType = trim((string)($input['billing_type'] ?? 'monthly'));
        $durationDays = (int)($input['duration_days'] ?? 0);
        $razorpayPlanId = trim((string)($input['razorpay_plan_id'] ?? ''));
        $isFeatured = !empty($input['is_featured']) ? 1 : 0;
        $sortOrder = max(0, (int)($input['sort_order'] ?? 0));
        $maxUsers = max(1, (int)($input['max_users'] ?? 1));
        $maxProducts = max(1, (int)($input['max_products'] ?? 1));
        $status = !empty($input['status']) && strtolower((string)$input['status']) === 'inactive'
            ? 'inactive'
            : 'active';
        $featuresInput = trim((string)($input['features'] ?? ''));

        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors[] = 'Plan name must be between 2 and 120 characters.';
        }

        $slug = SaaSBillingHelper::slugify($slugInput !== '' ? $slugInput : $name);
        $slug = $this->uniqueSlug($slug, $editingId);

        if ($price < 0) {
            $errors[] = 'Price cannot be negative.';
        }

        if ($offer > 0 && $offer >= $price && $price > 0) {
            $errors[] = 'Offer price must be lower than the regular price.';
        }

        if (!in_array($billingType, ['one_time', 'monthly', 'yearly'], true)) {
            $errors[] = 'Invalid billing type.';
        }

        if ($durationDays <= 0) {
            if ($billingType === 'monthly') {
                $durationDays = 30;
            } elseif ($billingType === 'yearly') {
                $durationDays = 365;
            } else {
                $errors[] = 'Duration days must be greater than 0.';
            }
        }

        if ($durationDays > 3650) {
            $errors[] = 'Duration days cannot exceed 3650.';
        }

        if ($maxUsers > 1000000) {
            $errors[] = 'Max users is too high.';
        }

        if ($maxProducts > 10000000) {
            $errors[] = 'Max products is too high.';
        }

        if ($razorpayPlanId !== '' && !preg_match('/^[a-zA-Z0-9_]+$/', $razorpayPlanId)) {
            $errors[] = 'Razorpay plan id contains invalid characters.';
        }

        $features = null;
        if ($featuresInput !== '') {
            $features = $this->normalizeFeaturesPayload($featuresInput);
            if ($features === null) {
                $errors[] = 'Features must be a valid JSON object or JSON list.';
            }
        } else {
            $features = $this->defaultFeaturesForPlan($slug, $name);
        }

        $payload = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'price' => $price,
            'offer_price' => $offer > 0 ? $offer : null,
            'billing_type' => $billingType,
            'duration_days' => $durationDays,
            'razorpay_plan_id' => $razorpayPlanId !== '' ? $razorpayPlanId : null,
            'is_featured' => $isFeatured,
            'sort_order' => $sortOrder,
            'status' => $status,
            'max_users' => $maxUsers,
            'max_products' => $maxProducts,
            'features' => $features,
            // Keep legacy columns in sync so old modules do not break.
            'billing_cycle' => $billingType,
            'is_active' => $status === 'active' ? 1 : 0,
            'updated_at' => SaaSBillingHelper::now(),
        ];

        if ($editingId === null) {
            $payload['created_at'] = SaaSBillingHelper::now();
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'payload' => $payload,
        ];
    }

    /**
     * Create plan.
     */
    public function createPlan(array $input): array {
        $this->ensureSchema();
        $validated = $this->validatePayload($input, null);
        if (!$validated['ok']) {
            return ['success' => false, 'errors' => $validated['errors']];
        }

        $id = $this->create($validated['payload']);
        return ['success' => true, 'id' => (int)$id];
    }

    /**
     * Update plan.
     */
    public function updatePlan(int $id, array $input): array {
        $this->ensureSchema();
        $existing = $this->find($id);
        if (!$existing) {
            return ['success' => false, 'errors' => ['Plan not found.']];
        }

        $validated = $this->validatePayload($input, $id);
        if (!$validated['ok']) {
            return ['success' => false, 'errors' => $validated['errors']];
        }

        $this->update($id, $validated['payload']);
        return ['success' => true, 'id' => $id];
    }

    /**
     * Delete plan safely.
     */
    public function deletePlan(int $id): array {
        $this->ensureSchema();
        $plan = $this->find($id);
        if (!$plan) {
            return ['success' => false, 'message' => 'Plan not found.'];
        }

        $activeSubs = 0;
        $activeSubs = (int)$this->db->query(
            "SELECT COUNT(*) FROM tenant_subscriptions WHERE plan_id = ? AND status IN ('pending', 'active', 'trial')",
            [$id]
        )->fetchColumn();

        if ($activeSubs > 0) {
            // Do not hard-delete plans with active bindings; deactivate instead.
            $this->update($id, [
                'status' => 'inactive',
                'is_active' => 0,
                'updated_at' => SaaSBillingHelper::now(),
            ]);
            return [
                'success' => true,
                'message' => 'Plan had active subscriptions and was disabled instead of deleted.',
            ];
        }

        $this->hardDelete($id);
        return ['success' => true, 'message' => 'Plan deleted successfully.'];
    }

    /**
     * Checkout price after plan-level offer.
     */
    public function checkoutPrice(array $plan): float {
        return SaaSBillingHelper::effectivePlanPrice($plan);
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string {
        $base = $slug;
        $i = 1;
        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = ?";
        $params = [$slug];
        if ($ignoreId !== null) {
            $sql .= " AND id != ?";
            $params[] = $ignoreId;
        }
        return (int)$this->db->query($sql, $params)->fetchColumn() > 0;
    }

    private function ensureSchema(): void {
        if (self::$schemaChecked) {
            return;
        }

        try {
            $this->db->query("SELECT 1 FROM {$this->table} LIMIT 1")->fetchColumn();
            self::$schemaChecked = true;
        } catch (\Throwable $e) {
            Logger::error('SaaS plan schema check failed', ['error' => $e->getMessage()]);
            throw new RuntimeException(
                'Missing saas_plans table. Run php cli/migrate.php before using SaaS billing features.',
                0,
                $e
            );
        }
    }

    private function normalizeFeaturesPayload(string $json): ?string {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return null;
        }

        $isAssoc = array_keys($decoded) !== range(0, count($decoded) - 1);
        $normalized = [];

        if ($isAssoc) {
            foreach ($decoded as $key => $value) {
                $k = $this->normalizeFeatureKey((string)$key);
                if ($k === '') {
                    continue;
                }
                $normalized[$k] = (bool)$value;
            }
        } else {
            foreach ($decoded as $item) {
                $k = $this->normalizeFeatureKey((string)$item);
                if ($k === '') {
                    continue;
                }
                $normalized[$k] = true;
            }
        }

        ksort($normalized);
        return json_encode($normalized, JSON_UNESCAPED_UNICODE);
    }

    private function normalizeFeatureKey(string $value): string {
        $value = strtolower(trim($value));
        $value = str_replace([' ', '-'], '_', $value);
        return preg_replace('/[^a-z0-9_]/', '', $value) ?: '';
    }

    private function defaultFeaturesForPlan(string $slug, string $name): string {
        $k = strtolower($slug !== '' ? $slug : $name);
        $features = [
            'audit_trail' => true,
            'basic_reports' => true,
            'customer_management' => true,
            'export_pdf' => true,
            'inventory' => true,
            'invoicing' => true,
            'payment_tracking' => true,
        ];

        if (strpos($k, 'professional') !== false || strpos($k, 'growth') !== false) {
            $features['advanced_reports'] = true;
            $features['ai_insights'] = true;
            $features['bulk_import'] = true;
            $features['custom_fields'] = true;
            $features['hr'] = true;
            $features['multi_user'] = true;
            $features['quotations'] = true;
            $features['sale_returns'] = true;
        }

        if (strpos($k, 'enterprise') !== false || $k === 'pro') {
            $features['advanced_reports'] = true;
            $features['api'] = true;
            $features['ai_insights'] = true;
            $features['backup_restore'] = true;
            $features['bulk_import'] = true;
            $features['custom_fields'] = true;
            $features['hr'] = true;
            $features['multi_warehouse'] = true;
            $features['multi_user'] = true;
            $features['quotations'] = true;
            $features['sale_returns'] = true;
        }

        ksort($features);
        return json_encode($features, JSON_UNESCAPED_UNICODE);
    }
}
