-- ============================================================================
-- 023_trim_plan_features_to_real_modules.sql
-- Keep plan feature JSON limited to capabilities that are actually present.
-- ============================================================================

UPDATE saas_plans
SET features = JSON_OBJECT(
    'audit_trail', true,
    'basic_reports', true,
    'customer_management', true,
    'export_pdf', true,
    'inventory', true,
    'invoicing', true,
    'payment_tracking', true,
    'sale_returns', true
)
WHERE name = 'Starter';

UPDATE saas_plans
SET features = JSON_OBJECT(
    'advanced_reports', true,
    'audit_trail', true,
    'backup_restore', true,
    'basic_reports', true,
    'customer_management', true,
    'export_pdf', true,
    'inventory', true,
    'invoicing', true,
    'multi_user', true,
    'payment_tracking', true,
    'quotations', true,
    'sale_returns', true,
    'webhooks', true
)
WHERE name = 'Professional';

UPDATE saas_plans
SET features = JSON_OBJECT(
    'advanced_reports', true,
    'audit_trail', true,
    'backup_restore', true,
    'basic_reports', true,
    'customer_management', true,
    'export_pdf', true,
    'inventory', true,
    'invoicing', true,
    'multi_user', true,
    'payment_tracking', true,
    'quotations', true,
    'sale_returns', true,
    'webhooks', true
)
WHERE name = 'Enterprise';
