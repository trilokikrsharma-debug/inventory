-- Align paid-plan HR access and promote demo tenants to the full showcase plan.

UPDATE saas_plans
SET features = JSON_MERGE_PATCH(
    COALESCE(features, JSON_OBJECT()),
    JSON_OBJECT('hr', TRUE)
)
WHERE slug IN ('professional', 'enterprise');

UPDATE companies
SET
    saas_plan_id = (
        SELECT id
        FROM (
            SELECT id
            FROM saas_plans
            WHERE slug = 'enterprise'
            ORDER BY id ASC
            LIMIT 1
        ) AS enterprise_plan
    ),
    plan = 'pro',
    updated_at = NOW()
WHERE is_demo = 1
  AND EXISTS (
      SELECT 1
      FROM saas_plans
      WHERE slug = 'enterprise'
  );
