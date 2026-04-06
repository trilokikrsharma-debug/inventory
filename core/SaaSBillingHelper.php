<?php
/**
 * SaaS Billing Helper
 *
 * Shared helpers for trusted amount math, promo/referral checks,
 * and small normalization routines used across billing modules.
 */
class SaaSBillingHelper {

    public const MIN_PAYABLE = 1.00;

    /**
     * Normalize any numeric input into a safe 2-decimal amount.
     */
    public static function money($value): float {
        if ($value === null || $value === '') {
            return 0.00;
        }
        $num = (float)$value;
        if (!is_finite($num) || $num < 0) {
            $num = 0.00;
        }
        return (float)number_format($num, 2, '.', '');
    }

    /**
     * Convert decimal amount to paise.
     */
    public static function toPaise($amount): int {
        return (int)round(self::money($amount) * 100);
    }

    /**
     * Return final plan price using offer_price when valid.
     */
    public static function effectivePlanPrice(array $plan): float {
        $price = self::money($plan['price'] ?? 0);
        $offer = self::money($plan['offer_price'] ?? 0);

        if ($offer > 0 && $offer < $price) {
            return $offer;
        }
        return $price;
    }

    /**
     * Convert plan feature keys into a consistent user-facing label.
     */
    public static function formatFeatureLabel(string $key): string {
        $featureLabels = [
            'inventory' => 'Inventory Management',
            'invoicing' => 'GST Invoicing',
            'multi_user' => 'Multi User',
            'quotations' => 'Quotations',
            'advanced_reports' => 'Advanced Reports',
            'backup_restore' => 'Backup & Restore',
            'backup' => 'Backup & Restore',
            'webhooks' => 'Webhooks',
            'basic_reports' => 'Basic Reports',
            'customer_management' => 'Customer Management',
            'payment_tracking' => 'Payment Tracking',
            'sale_returns' => 'Sale Returns',
            'audit_trail' => 'Audit Trail',
            'export_pdf' => 'PDF Export',
            'api' => 'API Access',
            'bulk_import' => 'Bulk Import',
            'ai_insights' => 'AI Insights',
            'custom_fields' => 'Custom Fields',
            'multi_warehouse' => 'Multi Warehouse',
            'crm' => 'CRM Tools',
            'hr' => 'HR Tools',
        ];

        $normalized = strtolower(trim($key));
        $normalized = str_replace([' ', '-'], '_', $normalized);
        $normalized = preg_replace('/[^a-z0-9_]/', '', $normalized) ?: '';
        if ($normalized === '') {
            return 'Feature';
        }
        if (isset($featureLabels[$normalized])) {
            return $featureLabels[$normalized];
        }
        return ucwords(str_replace('_', ' ', $normalized));
    }

    public static function planLimitsSummary(array $plan): array {
        $users = max(1, (int)($plan['max_users'] ?? 1));
        $products = max(1, (int)($plan['max_products'] ?? 1));

        return [
            'users' => $users,
            'products' => $products,
            'users_label' => $users >= 1000000 ? 'Unlimited users' : $users . ' users',
            'products_label' => $products >= 10000000 ? 'Unlimited products' : number_format($products) . ' products',
        ];
    }

    /**
     * Return enabled/disabled plan features for consistent rendering across views.
     *
     * @return array{enabled: array<int, string>, disabled: array<int, string>}
     */
    public static function extractPlanFeatures(array $plan, int $enabledLimit = 8, int $disabledLimit = 3): array {
        $raw = $plan['features'] ?? null;
        $decoded = null;
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }

        $enabled = [];
        $disabled = [];

        if (is_array($decoded)) {
            $isAssoc = array_keys($decoded) !== range(0, count($decoded) - 1);
            if ($isAssoc) {
                foreach ($decoded as $k => $v) {
                    $label = self::formatFeatureLabel((string)$k);
                    if ((bool)$v) {
                        $enabled[] = $label;
                    } else {
                        $disabled[] = $label;
                    }
                }
            } else {
                foreach ($decoded as $k) {
                    $enabled[] = self::formatFeatureLabel((string)$k);
                }
            }
        }

        if (empty($enabled)) {
            $enabled = ['Inventory Management', 'GST Invoicing', 'Basic Reports'];
        }

        return [
            'enabled' => array_slice(array_values(array_unique($enabled)), 0, max(0, $enabledLimit)),
            'disabled' => array_slice(array_values(array_unique($disabled)), 0, max(0, $disabledLimit)),
        ];
    }

    /**
     * Evaluate whether a plan exposes a given feature key.
     */
    public static function planHasFeature(array $plan, string $feature): bool {
        $feature = self::normalizeFeatureKey($feature);
        if ($feature === '') {
            return false;
        }

        $configured = self::extractConfiguredFeatureFlags($plan['features'] ?? null);
        if (!empty($configured)) {
            foreach (self::featureAliases($feature) as $alias) {
                if (array_key_exists($alias, $configured)) {
                    return (bool)$configured[$alias];
                }
            }
        }

        $slug = strtolower(trim((string)($plan['slug'] ?? $plan['name'] ?? 'starter')));
        if (!in_array($slug, ['starter', 'professional', 'enterprise'], true)) {
            $slug = 'starter';
        }

        $defaults = [
            'starter' => [
                'basic_reports', 'invoicing', 'inventory', 'customer_management',
                'payment_tracking', 'audit_trail', 'export_pdf',
            ],
            'professional' => [
                'basic_reports', 'invoicing', 'inventory', 'customer_management',
                'payment_tracking', 'audit_trail', 'export_pdf', 'multi_user',
                'quotations', 'advanced_reports', 'sale_returns', 'bulk_import', 'ai_insights', 'custom_fields', 'hr',
            ],
            'enterprise' => [
                'basic_reports', 'invoicing', 'inventory', 'customer_management',
                'payment_tracking', 'audit_trail', 'export_pdf', 'multi_user',
                'quotations', 'advanced_reports', 'sale_returns', 'backup_restore', 'api', 'bulk_import', 'ai_insights', 'custom_fields', 'multi_warehouse', 'hr',
            ],
        ];

        $allowed = $defaults[$slug] ?? [];
        foreach (self::featureAliases($feature) as $alias) {
            if (in_array($alias, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Default tenant-admin permission set constrained by plan features.
     *
     * @return array<int, string>
     */
    public static function tenantAdminPermissionNames(array $plan): array {
        $permissions = [
            'dashboard.view',
            'sales.view', 'sales.create', 'sales.edit', 'sales.delete',
            'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete',
            'payments.view', 'payments.create', 'payments.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
            'catalog.manage',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.manage',
            'settings.manage',
        ];

        if (self::planHasFeature($plan, 'basic_reports') || self::planHasFeature($plan, 'advanced_reports')) {
            $permissions[] = 'reports.view';
        }

        if (self::planHasFeature($plan, 'quotations')) {
            array_push($permissions, 'quotations.view', 'quotations.create', 'quotations.convert', 'quotations.delete');
        }

        if (self::planHasFeature($plan, 'sale_returns')) {
            array_push($permissions, 'returns.view', 'returns.create');
        }

        if (self::planHasFeature($plan, 'backup_restore')) {
            $permissions[] = 'backup.manage';
        }

        return array_values(array_unique($permissions));
    }

    private static function normalizeFeatureKey(string $feature): string {
        $normalized = strtolower(trim($feature));
        $normalized = str_replace([' ', '-'], '_', $normalized);
        return preg_replace('/[^a-z0-9_]/', '', $normalized) ?: '';
    }

    /**
     * @return array<string, bool>
     */
    private static function extractConfiguredFeatureFlags($raw): array {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        } else {
            $decoded = null;
        }

        if (!is_array($decoded)) {
            return [];
        }

        $isAssoc = array_keys($decoded) !== range(0, count($decoded) - 1);
        $result = [];

        if ($isAssoc) {
            foreach ($decoded as $key => $enabled) {
                $normalized = self::normalizeFeatureKey((string)$key);
                if ($normalized === '') {
                    continue;
                }
                $result[$normalized] = (bool)$enabled;
            }
            return $result;
        }

        foreach ($decoded as $item) {
            $normalized = self::normalizeFeatureKey((string)$item);
            if ($normalized === '') {
                continue;
            }
            $result[$normalized] = true;
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private static function featureAliases(string $feature): array {
        $aliases = [
            'api' => ['api', 'api_access'],
            'api_access' => ['api_access', 'api'],
            'backup' => ['backup', 'backup_restore'],
            'backup_restore' => ['backup_restore', 'backup'],
            'quotations' => ['quotations', 'quotation'],
            'advanced_reports' => ['advanced_reports', 'reports_advanced'],
            'basic_reports' => ['basic_reports', 'reports'],
            'customer_management' => ['customer_management', 'customers', 'crm'],
            'payment_tracking' => ['payment_tracking', 'payments'],
            'multi_user' => ['multi_user', 'team_users', 'users'],
            'multi_warehouse' => ['multi_warehouse', 'warehouse', 'warehouses'],
            'invoicing' => ['invoicing', 'invoice', 'gst_invoicing'],
            'hr' => ['hr'],
            'sale_returns' => ['sale_returns', 'returns', 'sale_return'],
        ];

        return $aliases[$feature] ?? [$feature];
    }

    /**
     * Calculate discount amount from promo inputs.
     */
    public static function discountAmount(
        float $baseAmount,
        string $discountType,
        float $discountValue,
        ?float $maxDiscountAmount = null
    ): float {
        $baseAmount = self::money($baseAmount);
        $discountValue = self::money($discountValue);
        $maxDiscountAmount = $maxDiscountAmount !== null ? self::money($maxDiscountAmount) : null;

        if ($baseAmount <= 0 || $discountValue <= 0) {
            return 0.00;
        }

        $discount = 0.00;
        if ($discountType === 'percentage') {
            $discount = self::money(($baseAmount * $discountValue) / 100);
        } elseif ($discountType === 'fixed') {
            $discount = min($discountValue, $baseAmount);
        }

        if ($maxDiscountAmount !== null && $maxDiscountAmount > 0) {
            $discount = min($discount, $maxDiscountAmount);
        }

        return self::money(max(0, min($discount, $baseAmount)));
    }

    /**
     * Apply discount with floor rules (>= Rs 1 by default).
     */
    public static function finalPayable(float $baseAmount, float $discountAmount, bool $allowBelowOne = false): float {
        $baseAmount = self::money($baseAmount);
        $discountAmount = self::money($discountAmount);
        $final = self::money($baseAmount - $discountAmount);

        if (!$allowBelowOne) {
            $final = max(self::MIN_PAYABLE, $final);
        } else {
            $final = max(0, $final);
        }
        return self::money($final);
    }

    /**
     * Parse plan IDs from JSON/csv into int array.
     */
    public static function parsePlanIds($raw): array {
        if (is_array($raw)) {
            $ids = $raw;
        } else {
            $raw = trim((string)$raw);
            if ($raw === '') {
                return [];
            }

            $json = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $ids = $json;
            } else {
                $ids = array_map('trim', explode(',', $raw));
            }
        }

        $clean = [];
        foreach ($ids as $id) {
            $n = (int)$id;
            if ($n > 0) {
                $clean[$n] = $n;
            }
        }
        return array_values($clean);
    }

    /**
     * Check if promo is applicable to a specific plan.
     */
    public static function isPromoApplicableToPlan(array $promo, int $planId): bool {
        $ids = self::parsePlanIds($promo['applicable_plan_ids'] ?? '');
        if (empty($ids)) {
            return true; // Empty means all plans.
        }
        return in_array($planId, $ids, true);
    }

    /**
     * Make URL-safe slug.
     */
    public static function slugify(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');
        return $value !== '' ? $value : 'plan';
    }

    /**
     * Current DB datetime string.
     */
    public static function now(): string {
        return date(DATETIME_FORMAT_DB);
    }

    /**
     * Validate date string in Y-m-d format.
     */
    public static function validDate(?string $date): bool {
        if ($date === null || $date === '') {
            return false;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        return strtotime($date) !== false;
    }

    /**
     * Generate unique-looking order code.
     */
    public static function generateOrderCode(string $prefix = 'SUB'): string {
        return strtoupper($prefix) . '-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}
