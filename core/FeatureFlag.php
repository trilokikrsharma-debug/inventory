<?php
/**
 * Feature Flags System — Gradual Rollout & Tenant Control
 * 
 * Controls feature visibility per-tenant and globally.
 * Supports percentage rollouts and plan-based gating.
 * 
 * Usage:
 *   if (FeatureFlag::isEnabled('ai_insights')) {
 *       // Show AI insights widget
 *   }
 *   
 *   if (FeatureFlag::isEnabled('bulk_import', $companyId)) {
 *       // Allow bulk import for this specific tenant
 *   }
 */
class FeatureFlag {

    private static $cache = null;
    private static $overrides = null;

    /**
     * Global feature flag definitions (defaults)
     * Override per-tenant via DB table `feature_flags`.
     */
    private static $defaults = [
        'ai_insights'       => ['enabled' => true,   'plans' => ['starter', 'professional', 'enterprise']],
        'bulk_import'       => ['enabled' => true,   'plans' => ['professional', 'enterprise']],
        'multi_warehouse'   => ['enabled' => false,  'plans' => ['enterprise']],
        'api_access'        => ['enabled' => true,   'plans' => ['professional', 'enterprise']],
        'webhooks'          => ['enabled' => true,   'plans' => ['professional', 'enterprise']],
        'advanced_reports'  => ['enabled' => true,   'plans' => ['professional', 'enterprise']],
        'custom_fields'     => ['enabled' => false,  'plans' => ['enterprise']],
        'audit_trail'       => ['enabled' => true,   'plans' => ['starter', 'professional', 'enterprise']],
        'export_pdf'        => ['enabled' => true,   'plans' => ['starter', 'professional', 'enterprise']],
        'backup_restore'    => ['enabled' => true,   'plans' => ['professional', 'enterprise']],
    ];

    /**
     * Check if a feature is enabled for the current or specified tenant
     */
    public static function isEnabled(string $feature, ?int $companyId = null): bool {
        // Check per-tenant override first
        $override = self::getTenantOverride($feature, $companyId);
        if ($override !== null) {
            return $override;
        }

        // Check global default
        $default = self::$defaults[$feature] ?? null;
        if ($default === null) {
            return false; // Unknown feature = disabled
        }

        if (!$default['enabled']) {
            return false;
        }

        // Check plan-based gating
        if (!empty($default['plans']) && class_exists('Tenant')) {
            $plan = self::normalizePlanKey(Tenant::plan() ?? 'starter');
            $allowedPlans = array_map([self::class, 'normalizePlanKey'], (array)$default['plans']);
            return in_array($plan, $allowedPlans, true);
        }

        return $default['enabled'];
    }

    /**
     * Get all feature flags with their status for the current tenant
     */
    public static function all(?int $companyId = null): array {
        $result = [];
        foreach (self::$defaults as $key => $config) {
            $result[$key] = [
                'enabled' => self::isEnabled($key, $companyId),
                'plans'   => $config['plans'] ?? [],
            ];
        }
        return $result;
    }

    /**
     * Set a per-tenant override (admin only)
     */
    public static function setOverride(int $companyId, string $feature, bool $enabled): void {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO feature_flags (company_id, feature, enabled, updated_at) 
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE enabled = ?, updated_at = NOW()",
            [$companyId, $feature, $enabled ? 1 : 0, $enabled ? 1 : 0]
        );
        self::$overrides = null; // Clear cache
        
        Logger::audit('feature_flag_changed', 'feature_flags', null, [
            'feature' => $feature, 'enabled' => $enabled, 'company_id' => $companyId
        ]);
    }

    /**
     * Get tenant-specific override from DB
     */
    private static function getTenantOverride(string $feature, ?int $companyId = null): ?bool {
        $cid = $companyId ?? (class_exists('Tenant') ? Tenant::id() : null);
        if ($cid === null) return null;

        // Lazy load overrides
        if (self::$overrides === null) {
            self::$overrides = [];
            try {
                $db = Database::getInstance();
                $rows = $db->query(
                    "SELECT feature, enabled FROM feature_flags WHERE company_id = ?",
                    [$cid]
                )->fetchAll();
                foreach ($rows as $row) {
                    self::$overrides[$row['feature']] = (bool)$row['enabled'];
                }
            } catch (\Exception $e) {
                // Table may not exist yet
                return null;
            }
        }

        return self::$overrides[$feature] ?? null;
    }

    /**
     * Register a new feature flag at runtime
     */
    public static function register(string $name, bool $defaultEnabled = false, array $plans = []): void {
        self::$defaults[$name] = ['enabled' => $defaultEnabled, 'plans' => $plans];
    }

    private static function normalizePlanKey(string $plan): string {
        $plan = strtolower(trim($plan));
        if ($plan === 'growth') {
            return 'professional';
        }
        if ($plan === 'pro') {
            return 'enterprise';
        }
        return $plan === '' ? 'starter' : $plan;
    }
}
