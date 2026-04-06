-- ============================================================================
-- 021_saas_plans_cleanup.sql
-- Normalize saas_plans defaults and remove duplicate seed rows safely.
-- ============================================================================

UPDATE saas_plans
SET max_users = 1
WHERE max_users IS NULL OR max_users < 1;

UPDATE saas_plans
SET features = JSON_OBJECT('api', false, 'inventory', true, 'invoicing', true)
WHERE name = 'Starter' AND (features IS NULL OR JSON_VALID(features) = 0);

UPDATE saas_plans
SET features = JSON_OBJECT('api', true, 'crm', true, 'inventory', true, 'invoicing', true)
WHERE name = 'Professional' AND (features IS NULL OR JSON_VALID(features) = 0);

UPDATE saas_plans
SET features = JSON_OBJECT('api', true, 'crm', true, 'hr', true, 'inventory', true, 'invoicing', true)
WHERE name = 'Enterprise' AND (features IS NULL OR JSON_VALID(features) = 0);

UPDATE saas_plans
SET slug = CONCAT(LOWER(REPLACE(TRIM(name), ' ', '-')), '-', id)
WHERE slug IS NULL OR slug = '';

UPDATE saas_plans
SET billing_type = CASE
    WHEN billing_type IN ('one_time', 'monthly', 'yearly') THEN billing_type
    WHEN billing_cycle IN ('one_time', 'monthly', 'yearly') THEN billing_cycle
    ELSE 'monthly'
END;

UPDATE saas_plans
SET duration_days = CASE
    WHEN duration_days IS NOT NULL AND duration_days > 0 THEN duration_days
    WHEN billing_type = 'yearly' THEN 365
    ELSE 30
END;

UPDATE saas_plans
SET status = CASE WHEN IFNULL(is_active, 1) = 1 THEN 'active' ELSE 'inactive' END
WHERE status IS NULL OR status = '';

DELETE p
FROM saas_plans p
INNER JOIN (
    SELECT name, MIN(id) AS keep_id
    FROM saas_plans
    WHERE name IN ('Starter', 'Professional', 'Enterprise')
    GROUP BY name
    HAVING COUNT(*) > 1
) k ON k.name = p.name
LEFT JOIN companies c ON c.saas_plan_id = p.id
LEFT JOIN tenant_subscriptions ts ON ts.plan_id = p.id
WHERE p.id <> k.keep_id
  AND c.id IS NULL
  AND ts.id IS NULL;
