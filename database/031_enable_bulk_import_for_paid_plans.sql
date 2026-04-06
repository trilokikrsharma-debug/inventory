UPDATE saas_plans
SET features = JSON_SET(COALESCE(features, JSON_OBJECT()), '$.bulk_import', true)
WHERE slug IN ('professional', 'enterprise');
