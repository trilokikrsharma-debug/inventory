-- ============================================================================
-- 014_saas_billing_system.sql
-- Production SaaS billing upgrade: plans, gateway logs, promos, referrals
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1) saas_plans enhancement (dynamic pricing + offers + featured + sorting)
-- ---------------------------------------------------------------------------
ALTER TABLE saas_plans
    ADD COLUMN IF NOT EXISTS slug VARCHAR(120) NULL AFTER name,
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER slug,
    ADD COLUMN IF NOT EXISTS offer_price DECIMAL(10,2) NULL AFTER price,
    ADD COLUMN IF NOT EXISTS billing_type ENUM('one_time','monthly','yearly') NULL AFTER offer_price,
    ADD COLUMN IF NOT EXISTS duration_days INT UNSIGNED NULL AFTER billing_type,
    ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER razorpay_plan_id,
    ADD COLUMN IF NOT EXISTS sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_featured,
    ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') NULL AFTER sort_order,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Keep backward compatibility columns in sync
UPDATE saas_plans
SET slug = CONCAT(LOWER(REPLACE(TRIM(name), ' ', '-')), '-', id)
WHERE slug IS NULL OR slug = '';

UPDATE saas_plans
SET billing_type = CASE
    WHEN billing_type IS NOT NULL AND billing_type <> '' THEN billing_type
    WHEN billing_cycle IN ('one_time','monthly','yearly') THEN billing_cycle
    ELSE 'monthly'
END;

UPDATE saas_plans
SET duration_days = CASE
    WHEN duration_days IS NOT NULL AND duration_days > 0 THEN duration_days
    WHEN billing_type = 'yearly' THEN 365
    WHEN billing_type = 'one_time' THEN 30
    ELSE 30
END;

UPDATE saas_plans
SET status = CASE WHEN IFNULL(is_active, 1) = 1 THEN 'active' ELSE 'inactive' END
WHERE status IS NULL OR status = '';

CREATE UNIQUE INDEX IF NOT EXISTS uq_saas_plans_slug ON saas_plans(slug);
CREATE INDEX IF NOT EXISTS idx_saas_plans_status_sort ON saas_plans(status, sort_order, is_featured);

-- ---------------------------------------------------------------------------
-- 2) tenant_subscriptions enhancement (idempotency + pricing + lifecycle)
-- ---------------------------------------------------------------------------
ALTER TABLE tenant_subscriptions
    ADD COLUMN IF NOT EXISTS subscription_type ENUM('one_time','recurring') NOT NULL DEFAULT 'recurring' AFTER status,
    ADD COLUMN IF NOT EXISTS order_code VARCHAR(80) NULL AFTER subscription_type,
    ADD COLUMN IF NOT EXISTS change_type ENUM('new','upgrade','renewal','downgrade') NOT NULL DEFAULT 'new' AFTER order_code,
    ADD COLUMN IF NOT EXISTS amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER change_type,
    ADD COLUMN IF NOT EXISTS original_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER amount,
    ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER original_amount,
    ADD COLUMN IF NOT EXISTS promo_code_id INT UNSIGNED NULL AFTER discount_amount,
    ADD COLUMN IF NOT EXISTS promo_code VARCHAR(40) NULL AFTER promo_code_id,
    ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending' AFTER promo_code,
    ADD COLUMN IF NOT EXISTS duration_days INT UNSIGNED NOT NULL DEFAULT 30 AFTER payment_status,
    ADD COLUMN IF NOT EXISTS razorpay_order_id VARCHAR(100) NULL AFTER razorpay_subscription_id,
    ADD COLUMN IF NOT EXISTS razorpay_payment_id VARCHAR(100) NULL AFTER razorpay_order_id,
    ADD COLUMN IF NOT EXISTS gateway_mode ENUM('order','subscription') NULL AFTER razorpay_payment_id,
    ADD COLUMN IF NOT EXISTS idempotency_key VARCHAR(80) NULL AFTER gateway_mode,
    ADD COLUMN IF NOT EXISTS started_at DATETIME NULL AFTER current_end,
    ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL AFTER started_at,
    ADD COLUMN IF NOT EXISTS cancelled_at DATETIME NULL AFTER expires_at,
    ADD COLUMN IF NOT EXISTS last_payment_at DATETIME NULL AFTER cancelled_at,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

CREATE UNIQUE INDEX IF NOT EXISTS uq_tenant_subscriptions_order_code ON tenant_subscriptions(order_code);
CREATE UNIQUE INDEX IF NOT EXISTS uq_tenant_subscriptions_idempotency ON tenant_subscriptions(idempotency_key);
CREATE INDEX IF NOT EXISTS idx_tenant_subscriptions_company_status ON tenant_subscriptions(company_id, status, payment_status);
CREATE INDEX IF NOT EXISTS idx_tenant_subscriptions_gateway ON tenant_subscriptions(razorpay_order_id, razorpay_subscription_id, razorpay_payment_id);

-- ---------------------------------------------------------------------------
-- 3) payment transaction table (for payment logs + failure analysis)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS saas_payment_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NULL,
    company_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'INR',
    status ENUM('created','captured','failed','refunded','error') NOT NULL DEFAULT 'created',
    gateway VARCHAR(20) NOT NULL DEFAULT 'razorpay',
    razorpay_order_id VARCHAR(100) NULL,
    razorpay_subscription_id VARCHAR(100) NULL,
    razorpay_payment_id VARCHAR(100) NULL,
    idempotency_key VARCHAR(80) NULL,
    source VARCHAR(30) NULL,
    failure_reason VARCHAR(500) NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_spt_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_spt_subscription FOREIGN KEY (subscription_id) REFERENCES tenant_subscriptions(id) ON DELETE SET NULL,
    UNIQUE KEY uq_spt_payment_id (razorpay_payment_id),
    UNIQUE KEY uq_spt_idempotency_key (idempotency_key),
    INDEX idx_spt_company_status (company_id, status, created_at),
    INDEX idx_spt_gateway_refs (razorpay_order_id, razorpay_subscription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 4) webhook idempotency table
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS saas_webhook_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_key VARCHAR(255) NOT NULL,
    event_name VARCHAR(80) NOT NULL,
    payload LONGTEXT NOT NULL,
    signature VARCHAR(255) NULL,
    process_status ENUM('received','processed','failed') NOT NULL DEFAULT 'received',
    error_message VARCHAR(500) NULL,
    processed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_saas_webhook_event_key (event_key),
    INDEX idx_saas_webhook_event_name (event_name),
    INDEX idx_saas_webhook_status (process_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 5) promo codes + usage tracking
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS promo_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    title VARCHAR(120) NOT NULL,
    description TEXT NULL,
    discount_type ENUM('fixed','percentage') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    max_discount_amount DECIMAL(10,2) NULL,
    minimum_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    usage_limit_total INT UNSIGNED NOT NULL DEFAULT 0,
    usage_limit_per_company INT UNSIGNED NOT NULL DEFAULT 0,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    valid_from DATETIME NULL,
    valid_to DATETIME NULL,
    applicable_plan_ids TEXT NULL,
    new_customers_only TINYINT(1) NOT NULL DEFAULT 0,
    allow_below_one TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_promo_code (code),
    INDEX idx_promo_status_dates (status, valid_from, valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS promo_code_usages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promo_code_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    subscription_id INT NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    final_amount DECIMAL(10,2) NOT NULL,
    used_at DATETIME NOT NULL,
    CONSTRAINT fk_promo_usage_promo FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id) ON DELETE CASCADE,
    CONSTRAINT fk_promo_usage_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_promo_usage_subscription FOREIGN KEY (subscription_id) REFERENCES tenant_subscriptions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_promo_usage_unique (promo_code_id, company_id, subscription_id),
    INDEX idx_promo_usage_lookup (promo_code_id, company_id, used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 6) referral columns on companies
-- ---------------------------------------------------------------------------
ALTER TABLE companies
    ADD COLUMN IF NOT EXISTS referral_code VARCHAR(40) NULL AFTER slug,
    ADD COLUMN IF NOT EXISTS referred_by_company_id INT UNSIGNED NULL AFTER referral_code,
    ADD COLUMN IF NOT EXISTS wallet_credit DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER referred_by_company_id;

CREATE UNIQUE INDEX IF NOT EXISTS uq_companies_referral_code ON companies(referral_code);
CREATE INDEX IF NOT EXISTS idx_companies_referred_by ON companies(referred_by_company_id);

-- ---------------------------------------------------------------------------
-- 7) referral reward rule config
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS referral_reward_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    reward_type ENUM('fixed_discount','wallet_credit','bonus_trial_days','one_time_commission_record') NOT NULL,
    reward_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    minimum_paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    auto_approve TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_referral_rules_status_sort (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default rule if none exists
INSERT INTO referral_reward_rules (name, reward_type, reward_value, minimum_paid_amount, auto_approve, sort_order, status, created_at, updated_at)
SELECT 'Default Referral Rule', 'wallet_credit', 100.00, 1.00, 0, 0, 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM referral_reward_rules LIMIT 1);

-- ---------------------------------------------------------------------------
-- 8) referrals + reward logs
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS referrals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referrer_company_id INT UNSIGNED NOT NULL,
    referred_company_id INT UNSIGNED NOT NULL,
    referral_code VARCHAR(40) NOT NULL,
    referral_status ENUM('pending','successful','rewarded','cancelled') NOT NULL DEFAULT 'pending',
    reward_type ENUM('fixed_discount','wallet_credit','bonus_trial_days','one_time_commission_record') NOT NULL,
    reward_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    reward_status ENUM('pending','rewarded','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_referrals_referrer_company FOREIGN KEY (referrer_company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_referrals_referred_company FOREIGN KEY (referred_company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY uq_referrals_referred_company (referred_company_id),
    INDEX idx_referrals_referrer (referrer_company_id, referral_status),
    INDEX idx_referrals_status (referral_status, reward_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referral_rewards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referral_id BIGINT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    reward_type VARCHAR(50) NOT NULL,
    reward_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    reward_note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_referral_rewards_referral FOREIGN KEY (referral_id) REFERENCES referrals(id) ON DELETE CASCADE,
    CONSTRAINT fk_referral_rewards_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_referral_rewards_company_date (company_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 9) hardening indexes for admin dashboard lists
-- ---------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_tenant_subscriptions_updated ON tenant_subscriptions(updated_at);
CREATE INDEX IF NOT EXISTS idx_tenant_subscriptions_plan_status ON tenant_subscriptions(plan_id, status);
CREATE INDEX IF NOT EXISTS idx_promo_codes_usage ON promo_codes(used_count, status);
CREATE INDEX IF NOT EXISTS idx_referrals_updated ON referrals(updated_at);

-- ---------------------------------------------------------------------------
-- 10) optional FK for companies.referred_by_company_id
-- ---------------------------------------------------------------------------
-- Uncomment when all historical rows are clean.
-- ALTER TABLE companies
--   ADD CONSTRAINT fk_companies_referred_by
--   FOREIGN KEY (referred_by_company_id) REFERENCES companies(id)
--   ON DELETE SET NULL;

-- ============================================================================
-- END
-- ============================================================================

