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

    /**
     * Resolve tenant from authenticated session.
     * Called once per request from index.php after Session::start().
     * 
     * @return void
     */
    public static function resolve() {
        if (self::$resolved) return;
        self::$resolved = true;

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
     * Get company plan.
     * 
     * @return string|null 'starter', 'growth', 'pro'
     */
    public static function plan() {
        return self::$company['plan'] ?? null;
    }

    /**
     * Check if a feature is allowed by the current plan.
     * 
     * @param string $feature Feature key
     * @return bool
     */
    public static function canUse($feature) {
        $plan = self::plan();
        $features = [
            'starter' => ['basic_reports', 'invoicing'],
            'growth'  => ['basic_reports', 'invoicing', 'multi_user', 'quotations', 'purchase_orders'],
            'pro'     => ['basic_reports', 'invoicing', 'multi_user', 'quotations', 'purchase_orders', 'ai_insights', 'advanced_reports', 'backup'],
        ];
        return in_array($feature, $features[$plan] ?? [], true);
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
    }

    /**
     * Reset tenant context (used in tests or after logout).
     */
    public static function reset() {
        self::$companyId = null;
        self::$company = null;
        self::$resolved = false;
    }
}
