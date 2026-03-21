-- ============================================================================
-- 019_tenant_integrity_hardening.sql
-- Final tenant integrity hardening for production-grade SaaS behavior.
-- Safe for existing databases when run through cli/migrate.php.
-- ============================================================================

-- 1) Guarantee that plan id=1 exists so signup/default-company flows never hit
--    a missing FK target during onboarding.
INSERT INTO saas_plans (
    id,
    name,
    razorpay_plan_id,
    price,
    billing_cycle,
    max_users,
    features,
    is_active,
    created_at
)
VALUES (
    1,
    'Starter',
    NULL,
    999.00,
    'monthly',
    3,
    JSON_OBJECT('inventory', true, 'invoicing', true, 'billing', true),
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE
    name = COALESCE(NULLIF(name, ''), VALUES(name)),
    price = CASE WHEN price IS NULL OR price <= 0 THEN VALUES(price) ELSE price END,
    billing_cycle = COALESCE(NULLIF(billing_cycle, ''), VALUES(billing_cycle)),
    max_users = CASE WHEN max_users IS NULL OR max_users <= 0 THEN VALUES(max_users) ELSE max_users END,
    features = COALESCE(features, VALUES(features)),
    is_active = 1;

UPDATE saas_plans
SET slug = CONCAT('starter-', id)
WHERE id = 1
  AND (slug IS NULL OR slug = '');

UPDATE saas_plans
SET status = 'active'
WHERE id = 1
  AND (status IS NULL OR status = '' OR status <> 'active');

UPDATE saas_plans
SET billing_type = 'monthly'
WHERE id = 1
  AND (billing_type IS NULL OR billing_type = '');

UPDATE saas_plans
SET duration_days = 30
WHERE id = 1
  AND (duration_days IS NULL OR duration_days <= 0);

-- 2) Ensure required baseline companies exist.
INSERT IGNORE INTO companies (
    id,
    name,
    slug,
    saas_plan_id,
    subscription_status,
    plan,
    status,
    is_demo,
    max_users,
    max_products
)
VALUES
    (1, 'My Business', 'my-business', 1, 'trial', 'pro', 'active', 0, 25, 5000),
    (999, 'InvenBill Demo Store', 'demo-store', 1, 'trial', 'pro', 'active', 1, 99, 9999);

-- 3) Repair companies that point at missing or null plans.
UPDATE companies c
LEFT JOIN saas_plans sp ON sp.id = c.saas_plan_id
SET c.saas_plan_id = 1
WHERE c.saas_plan_id IS NULL OR sp.id IS NULL;

UPDATE companies
SET subscription_status = 'trial'
WHERE subscription_status IS NULL OR subscription_status = '';

UPDATE companies
SET trial_ends_at = DATE_ADD(NOW(), INTERVAL 14 DAY)
WHERE trial_ends_at IS NULL;

-- 4) Super-admins may legitimately have no tenant; tenant users may not.
UPDATE users
SET company_id = 1
WHERE IFNULL(is_super_admin, 0) = 0
  AND company_id IS NULL;

ALTER TABLE users
    MODIFY COLUMN company_id INT UNSIGNED NULL DEFAULT NULL;
-- NOTE:
-- MySQL environments with binary logging often disallow trigger creation
-- for non-SUPER users (Error 1419), and CHECK + FK combinations can fail
-- on some schemas. We keep hard enforcement in application auth/signup flow,
-- while this migration guarantees a clean and consistent baseline dataset.

-- 5) Backfill company ownership and settings for every tenant company.
UPDATE companies c
JOIN (
    SELECT company_id, MIN(id) AS owner_user_id
    FROM users
    WHERE deleted_at IS NULL
      AND IFNULL(is_super_admin, 0) = 0
      AND company_id IS NOT NULL
    GROUP BY company_id
) u ON u.company_id = c.id
SET c.owner_user_id = u.owner_user_id
WHERE c.owner_user_id IS NULL;

INSERT INTO company_settings (
    company_id,
    company_name,
    company_email,
    company_phone,
    company_address,
    company_city,
    company_state,
    company_country,
    currency_symbol,
    currency_code,
    enable_gst,
    enable_tax,
    tax_rate,
    low_stock_threshold,
    invoice_prefix,
    purchase_prefix,
    payment_prefix,
    receipt_prefix
)
SELECT
    c.id,
    c.name,
    COALESCE(u.email, CONCAT('support+', c.slug, '@example.test')),
    COALESCE(u.phone, ''),
    '',
    '',
    '',
    'India',
    'Rs',
    'INR',
    1,
    1,
    18.00,
    10,
    'INV-',
    'PUR-',
    'PAY-',
    'REC-'
FROM companies c
LEFT JOIN users u ON u.id = c.owner_user_id
LEFT JOIN company_settings cs ON cs.company_id = c.id
WHERE cs.id IS NULL;

INSERT INTO customers (
    company_id,
    name,
    phone,
    email,
    address,
    is_active
)
SELECT
    c.id,
    'Walk-In Customer',
    '',
    '',
    '',
    1
FROM companies c
LEFT JOIN customers cu
    ON cu.company_id = c.id
   AND LOWER(cu.name) = 'walk-in customer'
   AND cu.deleted_at IS NULL
WHERE cu.id IS NULL;

-- 6) Allow platform-level activity entries without forcing a fake tenant id.
ALTER TABLE activity_log
    MODIFY COLUMN company_id INT UNSIGNED NULL DEFAULT NULL;

UPDATE activity_log al
LEFT JOIN users u ON u.id = al.user_id
SET al.company_id = NULL
WHERE al.company_id = 1
  AND u.id IS NOT NULL
  AND IFNULL(u.is_super_admin, 0) = 1
  AND u.company_id IS NULL;

-- ============================================================================
-- End
-- ============================================================================
