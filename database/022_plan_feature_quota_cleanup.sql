-- ============================================================================
-- 022_plan_feature_quota_cleanup.sql
-- Add plan-level product quotas and normalize canonical plan defaults.
-- ============================================================================

ALTER TABLE saas_plans
    ADD COLUMN max_products INT NOT NULL DEFAULT 100 AFTER max_users;

UPDATE saas_plans
SET max_users = CASE
        WHEN name = 'Starter' THEN 3
        WHEN name = 'Professional' THEN 10
        WHEN name = 'Enterprise' THEN 100
        ELSE max_users
    END,
    max_products = CASE
        WHEN name = 'Starter' THEN 500
        WHEN name = 'Professional' THEN 5000
        WHEN name = 'Enterprise' THEN 50000
        ELSE max_products
    END
WHERE name IN ('Starter', 'Professional', 'Enterprise');

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
    'api_access', true,
    'audit_trail', true,
    'backup_restore', true,
    'basic_reports', true,
    'bulk_import', true,
    'crm', true,
    'customer_management', true,
    'export_pdf', true,
    'inventory', true,
    'invoicing', true,
    'multi_user', true,
    'payment_tracking', true,
    'purchase_orders', true,
    'quotations', true,
    'sale_returns', true,
    'webhooks', true
)
WHERE name = 'Professional';

UPDATE saas_plans
SET features = JSON_OBJECT(
    'advanced_reports', true,
    'ai_insights', true,
    'api_access', true,
    'audit_trail', true,
    'backup_restore', true,
    'basic_reports', true,
    'bulk_import', true,
    'crm', true,
    'custom_fields', true,
    'customer_management', true,
    'export_pdf', true,
    'hr', true,
    'inventory', true,
    'invoicing', true,
    'multi_user', true,
    'multi_warehouse', true,
    'payment_tracking', true,
    'priority_support', true,
    'purchase_orders', true,
    'quotations', true,
    'sale_returns', true,
    'webhooks', true
)
WHERE name = 'Enterprise';

UPDATE companies c
JOIN saas_plans p ON p.id = c.saas_plan_id
SET c.max_users = CASE WHEN p.max_users > 0 THEN p.max_users ELSE c.max_users END,
    c.max_products = CASE WHEN p.max_products > 0 THEN p.max_products ELSE c.max_products END,
    c.plan = CASE
        WHEN LOWER(COALESCE(p.slug, p.name)) LIKE '%enterprise%' THEN 'enterprise'
        WHEN LOWER(COALESCE(p.slug, p.name)) LIKE '%professional%' OR LOWER(COALESCE(p.slug, p.name)) LIKE '%growth%' THEN 'professional'
        ELSE 'starter'
    END
WHERE c.saas_plan_id IS NOT NULL;
