<?php
/**
 * Tenant Resolution & Context
 * 
 * Singleton that holds the current tenant (company) context for the request.
 * Resolved once after authentication, then used throughout the request lifecycle.
 * 
 * SECURITY: company_id is ONLY sourced from the authenticated user's session.
 * It CANNOT be overridden via URL parameters, POST data, or headers.
 * 
 * Usage:
 *   Tenant::id()       → int|null  (current company_id)
 *   Tenant::company()  → array|null (full company row)
 *   Tenant::isDemo()   → bool
 *   Tenant::require()  → int (throws if not resolved)
 */
class Tenant {
    private static $companyId = null;
    private static $company = null;
    private static $resolved = false;
    private static $currentPlan = null;
    private static bool $planResolved = false;
    private static $columnCache = [];

    /**
     * Resolve tenant from authenticated session.
     * Called once per request from index.php after Session::start().
     * 
     * @return void
     */
    public static function resolve() {
        if (self::$resolved) return;
        self::$resolved = true;
        self::$currentPlan = null;
        self::$planResolved = false;

        if (!Session::isLoggedIn()) {
            self::$companyId = null;
            self::$company = null;
            return;
        }

        $user = Session::get('user');
        $companyId = (int)($user['company_id'] ?? 0);

        if ($companyId <= 0) {
            // Safety: user has no company — should not happen in normal flow
            error_log('[TENANT] User ID ' . ($user['id'] ?? '?') . ' has no company_id in session');
            self::$companyId = null;
            self::$company = null;
            return;
        }

        self::$companyId = $companyId;

        // Load company details (cached for the request)
        try {
            $db = Database::getInstance();
            self::$company = $db->query(
                "SELECT * FROM companies WHERE id = ? AND status = 'active'",
                [$companyId]
            )->fetch();

            if (!self::$company) {
                error_log('[TENANT] Company ID ' . $companyId . ' not found or inactive');
                self::$companyId = null;
            } elseif (defined('TENANT_AUTO_SYNC_SUBSCRIPTION_STATUS') ? TENANT_AUTO_SYNC_SUBSCRIPTION_STATUS : true) {
                $synced = self::syncSubscriptionStatus(self::$company);
                if (is_array($synced)) {
                    self::$company = $synced;
                }
            }
        } catch (\Exception $e) {
            error_log('[TENANT] Failed to load company: ' . $e->getMessage());
            self::$companyId = null;
            self::$company = null;
        }
    }

    /**
     * Get current company ID. Returns null if not resolved.
     * 
     * @return int|null
     */
    public static function id() {
        return self::$companyId;
    }

    /**
     * Get current company ID, throwing if not resolved.
     * Use this in contexts where a tenant MUST exist.
     * 
     * @return int
     * @throws \RuntimeException if no tenant is resolved
     */
    public static function require() {
        if (self::$companyId === null) {
            throw new \RuntimeException('Tenant context not available. User must be authenticated and assigned to a company.');
        }
        return self::$companyId;
    }

    /**
     * Get full company record.
     * 
     * @return array|null
     */
    public static function company() {
        return self::$company;
    }

    /**
     * Check if current tenant is a demo company.
     * 
     * @return bool
     */
    public static function isDemo() {
        return !empty(self::$company['is_demo']);
    }

    /**
     * Current request host, normalized without port or surrounding brackets.
     *
     * @return string
     */
    public static function host() {
        return self::normalizeHost($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? ''));
    }

    /**
     * Resolve a tenant company directly from the request host/subdomain.
     * Localhost and raw IP hosts return null for backward compatibility.
     *
     * @param string|null $host
     * @return array|null
     */
    public static function resolveFromHost($host = null) {
        $host = self::normalizeHost((string)($host ?? self::host()));
        if ($host === '' || self::isLocalHost($host)) {
            return null;
        }

        $identifier = self::extractTenantIdentifierFromHost($host);
        if ($identifier === null || $identifier === '') {
            return null;
        }

        try {
            $db = Database::getInstance();

            if (self::companyHasColumn('subdomain')) {
                $row = $db->query(
                    "SELECT * FROM companies WHERE subdomain = ? LIMIT 1",
                    [$identifier]
                )->fetch();
                if ($row) {
                    return $row;
                }
            }

            if (self::companyHasColumn('slug')) {
                $row = $db->query(
                    "SELECT * FROM companies WHERE slug = ? LIMIT 1",
                    [$identifier]
                )->fetch();
                if ($row) {
                    return $row;
                }
            }
        } catch (\Throwable $e) {
            error_log('[TENANT] Host resolution failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Check whether the current host matches the resolved tenant company.
     * Localhost/IP hosts are allowed when fallback is enabled.
     *
     * @return bool
     */
    public static function hostMatchesCurrentTenant() {
        if (!self::shouldEnforceHostMatch()) {
            return true;
        }

        if (Session::isSuperAdmin()) {
            return true;
        }

        $company = self::$company;
        if (!$company) {
            return true;
        }

        $tenantIdentifier = self::companyTenantIdentifier($company);
        if ($tenantIdentifier === '') {
            return true;
        }

        $host = self::host();
        if ($host === '' || self::isLocalHost($host)) {
            return defined('TENANT_ALLOW_LOCALHOST_FALLBACK') ? TENANT_ALLOW_LOCALHOST_FALLBACK : true;
        }

        $hostIdentifier = self::extractTenantIdentifierFromHost($host);
        if ($hostIdentifier === null || $hostIdentifier === '') {
            return false;
        }

        return hash_equals($tenantIdentifier, $hostIdentifier);
    }

    /**
     * Get company plan.
     * 
     * Canonical values: starter / professional / enterprise.
     *
     * @return string|null
     */
    public static function plan() {
        $plan = self::currentPlan();
        if ($plan) {
            return self::normalizePlanSlug($plan['slug'] ?? null, $plan['name'] ?? null);
        }

        $legacy = self::$company['plan'] ?? null;
        return self::mapLegacyPlanToCanonical($legacy);
    }

    /**
     * Get resolved SaaS plan record for current tenant.
     * Uses companies.saas_plan_id as source of truth with legacy fallback.
     *
     * @return array|null
     */
    public static function currentPlan() {
        if (self::$planResolved) {
            return self::$currentPlan;
        }
        self::$planResolved = true;
        self::$currentPlan = null;

        if (!self::$company || !self::$companyId) {
            return null;
        }

        try {
            $db = Database::getInstance();
            $saasPlanId = (int)(self::$company['saas_plan_id'] ?? 0);

            if ($saasPlanId > 0) {
                $row = $db->query(
                    "SELECT * FROM saas_plans WHERE id = ? LIMIT 1",
                    [$saasPlanId]
                )->fetch();
                if ($row) {
                    self::$currentPlan = $row;
                    return self::$currentPlan;
                }
            }

            // Legacy fallback for old tenants where companies.plan was the only field.
            $legacy = self::mapLegacyPlanToCanonical(self::$company['plan'] ?? null);
            if ($legacy !== null) {
                try {
                    $row = $db->query(
                        "SELECT * FROM saas_plans
                         WHERE (slug = ? OR LOWER(name) = ?)
                           AND (
                               status = 'active'
                               OR is_active = 1
                               OR status IS NULL
                           )
                         ORDER BY id ASC
                         LIMIT 1",
                        [$legacy, strtolower($legacy)]
                    )->fetch();
                } catch (\Throwable $e) {
                    $row = $db->query(
                        "SELECT * FROM saas_plans
                         WHERE (slug = ? OR LOWER(name) = ?)
                         ORDER BY id ASC
                         LIMIT 1",
                        [$legacy, strtolower($legacy)]
                    )->fetch();
                }

                if ($row) {
                    self::$currentPlan = $row;
                    return self::$currentPlan;
                }
            }
        } catch (\Throwable $e) {
            error_log('[TENANT] Failed to resolve current plan: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Current plan id (from saas_plans).
     *
     * @return int|null
     */
    public static function planId() {
        $plan = self::currentPlan();
        return $plan ? (int)($plan['id'] ?? 0) ?: null : null;
    }

    /**
     * Current plan display name.
     *
     * @return string|null
     */
    public static function planName() {
        $plan = self::currentPlan();
        if ($plan) {
            return (string)($plan['name'] ?? '');
        }

        $slug = self::plan();
        if ($slug === null) {
            return null;
        }
        return ucfirst($slug);
    }

    /**
     * Legacy alias (starter/growth/pro) for backward compatibility.
     *
     * @return string
     */
    public static function legacyPlan() {
        $canonical = self::plan() ?? 'starter';
        if ($canonical === 'enterprise') {
            return 'pro';
        }
        if ($canonical === 'professional') {
            return 'growth';
        }
        return 'starter';
    }

    /**
     * Check if a feature is allowed by the current plan.
     * 
     * @param string $feature Feature key
     * @param int|null $currentUsage Optional current usage count for quota-style features.
     * @param int $increment Optional delta to reserve/use.
     * @return bool
     */
    public static function canUse($feature, $currentUsage = null, $increment = 1) {
        $feature = self::normalizeFeatureKey((string)$feature);
        if ($feature === '') {
            return false;
        }

        if (self::isUsageKey($feature)) {
            if (self::$company && !self::isSubscriptionActive(self::$company, true)) {
                return false;
            }

            $limit = self::usageLimit($feature);
            if ($limit === null || $limit <= 0) {
                return true;
            }

            $usage = is_numeric($currentUsage) ? (int)$currentUsage : self::usageCount($feature);
            $increment = max(0, (int)$increment);
            return ($usage + $increment) <= $limit;
        }

        // First preference: explicit per-plan feature JSON from saas_plans.features
        $plan = self::currentPlan();
        $configured = self::extractConfiguredFeatures($plan['features'] ?? null);
        if (!empty($configured)) {
            $aliases = self::featureAliases($feature);
            foreach ($aliases as $key) {
                if (array_key_exists($key, $configured)) {
                    return (bool)$configured[$key];
                }
            }
        }

        // Fallback matrix for older plans without feature JSON.
        $slug = self::plan() ?? 'starter';
        $defaults = [
            'starter' => [
                'basic_reports', 'invoicing', 'inventory', 'customer_management',
                'payment_tracking', 'audit_trail', 'export_pdf'
            ],
            'professional' => [
                'basic_reports', 'invoicing', 'inventory', 'customer_management',
                'payment_tracking', 'audit_trail', 'export_pdf',
                'multi_user', 'quotations', 'purchase_orders',
                'advanced_reports', 'api_access', 'webhooks', 'bulk_import',
                'backup_restore', 'crm'
            ],
            'enterprise' => [
                'basic_reports', 'invoicing', 'inventory', 'customer_management',
                'payment_tracking', 'audit_trail', 'export_pdf',
                'multi_user', 'quotations', 'purchase_orders',
                'advanced_reports', 'api_access', 'webhooks', 'bulk_import',
                'backup_restore', 'crm', 'ai_insights', 'multi_warehouse',
                'custom_fields', 'priority_support', 'hr'
            ],
        ];

        $allowed = $defaults[$slug] ?? [];
        $aliases = self::featureAliases($feature);
        foreach ($aliases as $key) {
            if (in_array($key, $allowed, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Evaluate the current tenant subscription state.
     * Returns one of: active, trial, expired, inactive.
     *
     * @param array|null $company
     * @param bool $sync
     * @return string
     */
    public static function subscriptionStatus($company = null, $sync = true) {
        $company = is_array($company) ? $company : self::$company;
        if (!$company) {
            return 'inactive';
        }

        $state = self::evaluateSubscriptionState($company);
        if ($sync && (defined('TENANT_AUTO_SYNC_SUBSCRIPTION_STATUS') ? TENANT_AUTO_SYNC_SUBSCRIPTION_STATUS : true)) {
            self::syncSubscriptionStatus($company);
        }

        return $state['status'];
    }

    /**
     * Determine whether the tenant subscription is currently active.
     * Trial access counts as active while the trial window is open.
     *
     * @param array|null $company
     * @param bool $sync
     * @return bool
     */
    public static function isSubscriptionActive($company = null, $sync = true) {
        $company = is_array($company) ? $company : self::$company;
        if (!$company) {
            return false;
        }

        $state = self::evaluateSubscriptionState($company);
        if ($sync && (defined('TENANT_AUTO_SYNC_SUBSCRIPTION_STATUS') ? TENANT_AUTO_SYNC_SUBSCRIPTION_STATUS : true)) {
            self::syncSubscriptionStatus($company);
        }

        return in_array($state['status'], ['active', 'trial'], true);
    }

    /**
     * Sync companies.subscription_status to the computed lifecycle state.
     * Safe to call repeatedly.
     *
     * @param array|null $company
     * @return array|null
     */
    public static function syncSubscriptionStatus($company = null) {
        $company = is_array($company) ? $company : self::$company;
        if (!$company || empty($company['id'])) {
            return $company;
        }

        $state = self::evaluateSubscriptionState($company);
        $companyId = (int)$company['id'];
        $current = strtolower(trim((string)($company['subscription_status'] ?? '')));
        $desired = $state['status'];

        if ($current !== $desired) {
            try {
                $db = Database::getInstance();
                $db->query(
                    "UPDATE companies SET subscription_status = ?, updated_at = NOW() WHERE id = ?",
                    [$desired, $companyId]
                );
                $company['subscription_status'] = $desired;
            } catch (\Throwable $e) {
                error_log('[TENANT] Failed to sync subscription status: ' . $e->getMessage());
            }
        }

        if (self::$company && (int)(self::$company['id'] ?? 0) === $companyId) {
            self::$company = array_merge(self::$company, $company);
        }

        return $company;
    }

    /**
     * Get the configured limit for a quota-style feature.
     *
     * @param string $feature
     * @return int|null
     */
    public static function usageLimit($feature) {
        $feature = self::normalizeFeatureKey((string)$feature);
        if (!self::isUsageKey($feature)) {
            return null;
        }

        $company = self::$company;
        if (!$company) {
            return null;
        }

        $companyLimit = self::companyLimitValue($company, $feature);
        if ($companyLimit !== null && $companyLimit > 0) {
            return $companyLimit;
        }

        $plan = self::currentPlan();
        $planLimit = self::planLimitValue($plan, $feature);
        if ($planLimit !== null && $planLimit > 0) {
            return $planLimit;
        }

        return null;
    }

    /**
     * Count the current usage for a quota-style feature.
     *
     * @param string $feature
     * @return int
     */
    public static function usageCount($feature) {
        $feature = self::normalizeFeatureKey((string)$feature);
        if (!self::isUsageKey($feature) || !self::$companyId) {
            return 0;
        }

        try {
            $db = Database::getInstance();
            $companyId = self::require();

            if ($feature === 'max_users') {
                return (int)$db->query(
                    "SELECT COUNT(*) FROM users WHERE company_id = ? AND deleted_at IS NULL",
                    [$companyId]
                )->fetchColumn();
            }

            if ($feature === 'max_products') {
                return (int)$db->query(
                    "SELECT COUNT(*) FROM products WHERE company_id = ? AND deleted_at IS NULL",
                    [$companyId]
                )->fetchColumn();
            }
        } catch (\Throwable $e) {
            error_log('[TENANT] Failed to load usage count: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Set tenant context manually (used during signup/login).
     * 
     * @param int        $companyId
     * @param array|null $company  Optional pre-loaded company row
     */
    public static function set($companyId, $company = null) {
        self::$companyId = (int)$companyId;
        self::$company = $company;
        self::$resolved = true;
        self::$currentPlan = null;
        self::$planResolved = false;
    }

    /**
     * Reset tenant context (used in tests or after logout).
     */
    public static function reset() {
        self::$companyId = null;
        self::$company = null;
        self::$resolved = false;
        self::$currentPlan = null;
        self::$planResolved = false;
    }

    private static function mapLegacyPlanToCanonical($legacy) {
        $legacy = strtolower(trim((string)$legacy));
        if ($legacy === '') {
            return null;
        }

        if (in_array($legacy, ['pro', 'enterprise', 'premium'], true)) {
            return 'enterprise';
        }
        if (in_array($legacy, ['growth', 'professional', 'business'], true)) {
            return 'professional';
        }
        return 'starter';
    }

    private static function normalizePlanSlug($slug, $name = null) {
        $candidate = strtolower(trim((string)$slug));
        if ($candidate === '') {
            $candidate = strtolower(trim((string)$name));
        }

        if ($candidate === '') {
            return 'starter';
        }

        if (strpos($candidate, 'enterprise') !== false || $candidate === 'pro' || $candidate === 'premium') {
            return 'enterprise';
        }

        if (strpos($candidate, 'professional') !== false || strpos($candidate, 'growth') !== false || $candidate === 'business') {
            return 'professional';
        }

        return 'starter';
    }

    private static function normalizeFeatureKey(string $value): string {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }
        $value = str_replace([' ', '-'], '_', $value);
        return preg_replace('/[^a-z0-9_]/', '', $value) ?: '';
    }

    private static function isUsageKey(string $feature): bool {
        return in_array($feature, ['max_users', 'max_products'], true);
    }

    private static function normalizeHost(string $host): string {
        $host = strtolower(trim($host));
        if ($host === '') {
            return '';
        }

        $host = trim($host, "[]");
        $host = preg_replace('/:\d+$/', '', $host);
        return trim((string)$host, '.');
    }

    private static function isLocalHost(string $host): bool {
        $host = self::normalizeHost($host);
        if ($host === '') {
            return true;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        return (bool)filter_var($host, FILTER_VALIDATE_IP);
    }

    private static function shouldEnforceHostMatch(): bool {
        return defined('TENANT_HOST_ENFORCEMENT') ? TENANT_HOST_ENFORCEMENT : false;
    }

    private static function normalizedBaseDomain(): string {
        $base = trim((string)(defined('TENANT_BASE_DOMAIN') ? TENANT_BASE_DOMAIN : ''));
        if ($base === '' || self::isLocalHost($base)) {
            return '';
        }
        return self::normalizeHost($base);
    }

    private static function extractTenantIdentifierFromHost(string $host): ?string {
        $host = self::normalizeHost($host);
        if ($host === '' || self::isLocalHost($host)) {
            return null;
        }

        $baseDomain = self::normalizedBaseDomain();
        if ($baseDomain !== '' && $host === $baseDomain) {
            return null;
        }

        if ($baseDomain !== '' && str_ends_with($host, '.' . $baseDomain)) {
            $prefix = substr($host, 0, -1 * (strlen($baseDomain) + 1));
            $parts = array_values(array_filter(explode('.', $prefix), 'strlen'));
            return self::normalizeTenantIdentifier($parts[0] ?? '');
        }

        $parts = array_values(array_filter(explode('.', $host), 'strlen'));
        if (count($parts) < 3) {
            return null;
        }

        return self::normalizeTenantIdentifier($parts[0] ?? '');
    }

    private static function normalizeTenantIdentifier(string $value): string {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        return preg_replace('/[^a-z0-9-]/', '', $value) ?: '';
    }

    private static function companyTenantIdentifier(array $company): string {
        $candidate = (string)($company['subdomain'] ?? $company['slug'] ?? '');
        return self::normalizeTenantIdentifier($candidate);
    }

    private static function companyHasColumn(string $column): bool {
        $column = strtolower(trim($column));
        $cacheKey = 'companies.' . $column;
        if (array_key_exists($cacheKey, self::$columnCache)) {
            return (bool)self::$columnCache[$cacheKey];
        }

        try {
            $db = Database::getInstance();
            $row = $db->query(
                "SELECT COUNT(*) AS cnt
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'companies'
                   AND COLUMN_NAME = ?",
                [$column]
            )->fetch();
            self::$columnCache[$cacheKey] = !empty($row['cnt']);
        } catch (\Throwable $e) {
            self::$columnCache[$cacheKey] = false;
        }

        return (bool)self::$columnCache[$cacheKey];
    }

    private static function subscriptionHasColumn(string $column): bool {
        $column = strtolower(trim($column));
        $cacheKey = 'tenant_subscriptions.' . $column;
        if (array_key_exists($cacheKey, self::$columnCache)) {
            return (bool)self::$columnCache[$cacheKey];
        }

        try {
            $db = Database::getInstance();
            $row = $db->query(
                "SELECT COUNT(*) AS cnt
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'tenant_subscriptions'
                   AND COLUMN_NAME = ?",
                [$column]
            )->fetch();
            self::$columnCache[$cacheKey] = !empty($row['cnt']);
        } catch (\Throwable $e) {
            self::$columnCache[$cacheKey] = false;
        }

        return (bool)self::$columnCache[$cacheKey];
    }

    private static function companyLimitValue(array $company, string $feature): ?int {
        if (!self::isUsageKey($feature)) {
            return null;
        }

        $field = substr($feature, 4);
        $value = $company[$feature] ?? $company[$field] ?? null;
        if (is_numeric($value) && (int)$value > 0) {
            return (int)$value;
        }

        return null;
    }

    private static function planLimitValue($plan, string $feature): ?int {
        if (!is_array($plan) || !self::isUsageKey($feature)) {
            return null;
        }

        $value = $plan[$feature] ?? ($plan[substr($feature, 4)] ?? null);
        if (is_numeric($value) && (int)$value > 0) {
            return (int)$value;
        }

        return null;
    }

    private static function evaluateSubscriptionState(array $company): array {
        $companyId = (int)($company['id'] ?? 0);
        $status = strtolower(trim((string)($company['subscription_status'] ?? $company['status'] ?? 'inactive')));
        $trialEndsAt = self::parseDateToTimestamp($company['trial_ends_at'] ?? null);

        if (in_array($status, ['inactive', 'suspended', 'cancelled'], true)) {
            return [
                'status' => $status,
                'active' => false,
                'source' => 'company',
                'expires_at' => null,
            ];
        }

        $subscriptionState = self::latestSubscriptionWindowState($companyId);
        if ($subscriptionState['active']) {
            return [
                'status' => 'active',
                'active' => true,
                'source' => 'subscription',
                'expires_at' => $subscriptionState['expires_at'],
            ];
        }

        if ($trialEndsAt !== null && $trialEndsAt >= time() && in_array($status, ['trial', 'active'], true)) {
            return [
                'status' => 'trial',
                'active' => true,
                'source' => 'trial',
                'expires_at' => date(DATETIME_FORMAT_DB, $trialEndsAt),
            ];
        }

        if ($trialEndsAt !== null && $trialEndsAt < time()) {
            return [
                'status' => 'expired',
                'active' => false,
                'source' => 'trial',
                'expires_at' => date(DATETIME_FORMAT_DB, $trialEndsAt),
            ];
        }

        if ($status === 'trial') {
            return [
                'status' => 'expired',
                'active' => false,
                'source' => 'trial',
                'expires_at' => $trialEndsAt !== null ? date(DATETIME_FORMAT_DB, $trialEndsAt) : null,
            ];
        }

        if ($status === 'active') {
            if (empty($subscriptionState['has_rows'])) {
                return [
                    'status' => 'active',
                    'active' => true,
                    'source' => 'company',
                    'expires_at' => null,
                ];
            }

            return [
                'status' => 'expired',
                'active' => false,
                'source' => 'subscription',
                'expires_at' => $subscriptionState['expires_at'],
            ];
        }

        return [
            'status' => $status === '' ? 'inactive' : $status,
            'active' => false,
            'source' => 'company',
            'expires_at' => null,
        ];
    }

    private static function latestSubscriptionWindowState(int $companyId): array {
        if ($companyId <= 0) {
            return ['active' => false, 'has_rows' => false, 'expires_at' => null];
        }

        try {
            $db = Database::getInstance();
            $rows = $db->query(
                "SELECT *
                 FROM tenant_subscriptions
                 WHERE company_id = ?
                 ORDER BY id DESC
                 LIMIT 10",
                [$companyId]
            )->fetchAll();
        } catch (\Throwable $e) {
            error_log('[TENANT] Failed to load subscriptions: ' . $e->getMessage());
            return ['active' => false, 'has_rows' => false, 'expires_at' => null];
        }

        if (empty($rows)) {
            return ['active' => false, 'has_rows' => false, 'expires_at' => null];
        }

        foreach ($rows as $row) {
            if (!self::subscriptionRowIsActive($row)) {
                continue;
            }

            $expiresAt = self::subscriptionRowExpiryTimestamp($row);
            if ($expiresAt === null) {
                return ['active' => true, 'has_rows' => true, 'expires_at' => null];
            }

            if ($expiresAt >= time()) {
                return [
                    'active' => true,
                    'has_rows' => true,
                    'expires_at' => date(DATETIME_FORMAT_DB, $expiresAt),
                ];
            }
        }

        return ['active' => false, 'has_rows' => true, 'expires_at' => null];
    }

    private static function subscriptionRowIsActive(array $row): bool {
        $status = strtolower(trim((string)($row['status'] ?? '')));
        $paymentStatus = strtolower(trim((string)($row['payment_status'] ?? '')));

        if ($paymentStatus !== '') {
            return in_array($paymentStatus, ['paid', 'captured'], true);
        }

        return in_array($status, ['active', 'trial', 'authenticated', 'completed'], true);
    }

    private static function subscriptionRowExpiryTimestamp(array $row): ?int {
        $expiresAt = self::parseDateToTimestamp($row['expires_at'] ?? null);
        if ($expiresAt !== null) {
            return $expiresAt;
        }

        $currentEnd = self::parseDateToTimestamp($row['current_end'] ?? null);
        if ($currentEnd !== null) {
            return $currentEnd;
        }

        $durationDays = (int)($row['duration_days'] ?? 0);
        if ($durationDays > 0) {
            $base = self::parseDateToTimestamp($row['last_payment_at'] ?? null)
                ?? self::parseDateToTimestamp($row['started_at'] ?? null)
                ?? self::parseDateToTimestamp($row['created_at'] ?? null);
            if ($base !== null) {
                return strtotime('+' . $durationDays . ' days', $base) ?: null;
            }
        }

        return null;
    }

    private static function parseDateToTimestamp($value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string)$value);
        return $timestamp === false ? null : $timestamp;
    }

    private static function extractConfiguredFeatures($raw): array {
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

    private static function featureAliases(string $feature): array {
        $feature = self::normalizeFeatureKey($feature);
        $aliases = [
            'api' => ['api', 'api_access'],
            'api_access' => ['api_access', 'api'],
            'backup' => ['backup', 'backup_restore'],
            'backup_restore' => ['backup_restore', 'backup'],
            'quotations' => ['quotations', 'quotation'],
            'purchase_orders' => ['purchase_orders', 'purchase_order', 'po'],
            'ai_insights' => ['ai_insights', 'ai'],
            'advanced_reports' => ['advanced_reports', 'reports_advanced'],
            'basic_reports' => ['basic_reports', 'reports'],
            'customer_management' => ['customer_management', 'customers', 'crm'],
            'payment_tracking' => ['payment_tracking', 'payments'],
            'multi_user' => ['multi_user', 'team_users', 'users'],
            'inventory' => ['inventory'],
            'invoicing' => ['invoicing', 'invoice', 'gst_invoicing'],
            'webhooks' => ['webhooks'],
            'bulk_import' => ['bulk_import', 'import'],
            'custom_fields' => ['custom_fields'],
            'audit_trail' => ['audit_trail'],
            'export_pdf' => ['export_pdf', 'pdf_export'],
            'multi_warehouse' => ['multi_warehouse'],
            'priority_support' => ['priority_support'],
            'hr' => ['hr'],
        ];

        return $aliases[$feature] ?? [$feature];
    }
}
