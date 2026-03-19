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

        $order = [];
        if ($this->hasColumn('sort_order')) {
            $order[] = 'sort_order ASC';
        }
        $order[] = 'id ASC';

        return $this->db->query(
            "SELECT * FROM {$this->table} ORDER BY " . implode(', ', $order)
        )->fetchAll();
    }

    /**
     * List active plans for tenant checkout.
     */
    public function listForCheckout(): array {
        $this->ensureSchema();

        $where = [];
        if ($this->hasColumn('status')) {
            $where[] = "status = 'active'";
        } elseif ($this->hasColumn('is_active')) {
            $where[] = "is_active = 1";
        }

        $order = [];
        if ($this->hasColumn('is_featured')) {
            $order[] = 'is_featured DESC';
        }
        if ($this->hasColumn('sort_order')) {
            $order[] = 'sort_order ASC';
        }
        $order[] = 'id ASC';

        $sql = "SELECT * FROM {$this->table}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY " . implode(', ', $order);

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Find active plan by id.
     */
    public function findActive(int $id): ?array {
        $this->ensureSchema();

        $where = ["id = ?"];
        $params = [$id];

        if ($this->hasColumn('status')) {
            $where[] = "status = 'active'";
        } elseif ($this->hasColumn('is_active')) {
            $where[] = "is_active = 1";
        }

        $row = $this->db->query(
            "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where) . " LIMIT 1",
            $params
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
            'features' => $features,
            // Keep legacy columns in sync so old modules do not break.
            'billing_cycle' => $billingType,
            'is_active' => $status === 'active' ? 1 : 0,
            'updated_at' => SaaSBillingHelper::now(),
        ];

        if ($editingId === null) {
            $payload['created_at'] = SaaSBillingHelper::now();
        }

        $payload = $this->filterToExistingColumns($payload);

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
        try {
            $activeSubs = (int)$this->db->query(
                "SELECT COUNT(*) FROM tenant_subscriptions WHERE plan_id = ? AND status IN ('pending', 'active', 'trial')",
                [$id]
            )->fetchColumn();
        } catch (\Throwable $e) {
            // Older schemas may not have status yet; fallback to safe-disable behavior.
            $activeSubs = 1;
        }

        if ($activeSubs > 0) {
            // Do not hard-delete plans with active bindings; deactivate instead.
            $this->update($id, $this->filterToExistingColumns([
                'status' => 'inactive',
                'is_active' => 0,
                'updated_at' => SaaSBillingHelper::now(),
            ]));
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
        if (!$this->hasColumn('slug')) {
            return 'plan-' . substr(bin2hex(random_bytes(4)), 0, 8);
        }

        $base = $slug;
        $i = 1;
        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool {
        if (!$this->hasColumn('slug')) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = ?";
        $params = [$slug];
        if ($ignoreId !== null) {
            $sql .= " AND id != ?";
            $params[] = $ignoreId;
        }
        return (int)$this->db->query($sql, $params)->fetchColumn() > 0;
    }

    private function filterToExistingColumns(array $payload): array {
        $columns = $this->availableColumns();
        if (empty($columns)) {
            return $payload;
        }

        return array_filter(
            $payload,
            static fn($col) => isset($columns[$col]),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function availableColumns(): array {
        try {
            $rows = $this->db->query("SHOW COLUMNS FROM {$this->table}")->fetchAll();
            $map = [];
            foreach ($rows as $row) {
                $field = (string)($row['Field'] ?? '');
                if ($field !== '') {
                    $map[$field] = true;
                }
            }
            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function hasColumn(string $column): bool {
        $columns = $this->availableColumns();
        return isset($columns[$column]);
    }

    private function indexExists(string $indexName): bool {
        try {
            $count = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
                [$this->table, $indexName]
            )->fetchColumn();
            return $count > 0;
        } catch (\Throwable $e) {
            return true;
        }
    }

    private function ensureSchema(): void {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;

        try {
            $exists = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
                [$this->table]
            )->fetchColumn();

            if ($exists === 0) {
                $this->db->query(
                    "CREATE TABLE {$this->table} (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(120) NOT NULL,
                        slug VARCHAR(120) NULL,
                        description TEXT NULL,
                        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        offer_price DECIMAL(10,2) NULL,
                        billing_type ENUM('one_time','monthly','yearly') NOT NULL DEFAULT 'monthly',
                        duration_days INT UNSIGNED NOT NULL DEFAULT 30,
                        razorpay_plan_id VARCHAR(100) NULL,
                        is_featured TINYINT(1) NOT NULL DEFAULT 0,
                        sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                        billing_cycle VARCHAR(20) NULL DEFAULT 'monthly',
                        is_active TINYINT(1) NOT NULL DEFAULT 1,
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                );
            } else {
                $columns = $this->availableColumns();
                $alter = [];

                if (!isset($columns['slug'])) {
                    $alter[] = "ADD COLUMN slug VARCHAR(120) NULL AFTER name";
                }
                if (!isset($columns['description'])) {
                    $alter[] = "ADD COLUMN description TEXT NULL AFTER slug";
                }
                if (!isset($columns['offer_price'])) {
                    $alter[] = "ADD COLUMN offer_price DECIMAL(10,2) NULL AFTER price";
                }
                if (!isset($columns['billing_type'])) {
                    $alter[] = "ADD COLUMN billing_type ENUM('one_time','monthly','yearly') NULL AFTER offer_price";
                }
                if (!isset($columns['duration_days'])) {
                    $alter[] = "ADD COLUMN duration_days INT UNSIGNED NULL AFTER billing_type";
                }
                if (!isset($columns['is_featured'])) {
                    $alter[] = "ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER razorpay_plan_id";
                }
                if (!isset($columns['sort_order'])) {
                    $alter[] = "ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_featured";
                }
                if (!isset($columns['status'])) {
                    $alter[] = "ADD COLUMN status ENUM('active','inactive') NULL AFTER sort_order";
                }
                if (!isset($columns['updated_at'])) {
                    $alter[] = "ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
                }

                if (!empty($alter)) {
                    $this->db->query("ALTER TABLE {$this->table} " . implode(', ', $alter));
                }
            }

            // Keep compatibility defaults valid after schema patching.
            if ($this->hasColumn('slug')) {
                $this->db->query(
                    "UPDATE {$this->table}
                     SET slug = CONCAT(LOWER(REPLACE(TRIM(name), ' ', '-')), '-', id)
                     WHERE slug IS NULL OR slug = ''"
                );
            }

            if ($this->hasColumn('billing_type') && $this->hasColumn('billing_cycle')) {
                $this->db->query(
                    "UPDATE {$this->table}
                     SET billing_type = CASE
                         WHEN billing_type IS NOT NULL AND billing_type <> '' THEN billing_type
                         WHEN billing_cycle IN ('one_time','monthly','yearly') THEN billing_cycle
                         ELSE 'monthly'
                     END"
                );
            }

            if ($this->hasColumn('duration_days') && $this->hasColumn('billing_type')) {
                $this->db->query(
                    "UPDATE {$this->table}
                     SET duration_days = CASE
                         WHEN duration_days IS NOT NULL AND duration_days > 0 THEN duration_days
                         WHEN billing_type = 'yearly' THEN 365
                         ELSE 30
                     END"
                );
            }

            if ($this->hasColumn('status') && $this->hasColumn('is_active')) {
                $this->db->query(
                    "UPDATE {$this->table}
                     SET status = CASE WHEN IFNULL(is_active, 1) = 1 THEN 'active' ELSE 'inactive' END
                     WHERE status IS NULL OR status = ''"
                );
            }

            if ($this->hasColumn('slug') && !$this->indexExists('uq_saas_plans_slug')) {
                $this->db->query("CREATE UNIQUE INDEX uq_saas_plans_slug ON {$this->table}(slug)");
            }

            if ($this->hasColumn('status') && $this->hasColumn('sort_order') && $this->hasColumn('is_featured')
                && !$this->indexExists('idx_saas_plans_status_sort')) {
                $this->db->query("CREATE INDEX idx_saas_plans_status_sort ON {$this->table}(status, sort_order, is_featured)");
            }
        } catch (\Throwable $e) {
            Logger::error('SaaS plan schema check failed', ['error' => $e->getMessage()]);
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
            'inventory' => true,
            'invoicing' => true,
            'api' => false,
            'crm' => false,
            'hr' => false,
        ];

        if (strpos($k, 'professional') !== false || strpos($k, 'growth') !== false) {
            $features['api'] = true;
            $features['crm'] = true;
        }

        if (strpos($k, 'enterprise') !== false || $k === 'pro') {
            $features['api'] = true;
            $features['crm'] = true;
            $features['hr'] = true;
        }

        ksort($features);
        return json_encode($features, JSON_UNESCAPED_UNICODE);
    }
}
