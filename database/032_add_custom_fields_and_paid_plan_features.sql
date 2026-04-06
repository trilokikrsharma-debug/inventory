ALTER TABLE products
    ADD COLUMN IF NOT EXISTS custom_fields JSON NULL AFTER description;

ALTER TABLE customers
    ADD COLUMN IF NOT EXISTS custom_fields JSON NULL AFTER tax_number;

UPDATE saas_plans
SET features = JSON_SET(
    JSON_SET(
        COALESCE(features, JSON_OBJECT()),
        '$.ai_insights', true
    ),
    '$.custom_fields', true
)
WHERE slug IN ('professional', 'enterprise');
