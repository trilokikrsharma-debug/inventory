-- =====================================================================
-- InvenBill Pro — Enterprise Platform Database Migration
-- New tables for API tokens, feature flags, webhooks, and job queue.
-- Run AFTER enterprise_hardening.sql
-- =====================================================================

-- ─── API Tokens (stateless auth for integrations) ───
CREATE TABLE IF NOT EXISTS api_tokens (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    token_hash      VARCHAR(64) NOT NULL UNIQUE,
    scopes          JSON DEFAULT NULL,
    is_active       TINYINT(1) DEFAULT 1,
    expires_at      TIMESTAMP NULL DEFAULT NULL,
    last_used_at    TIMESTAMP NULL DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_api_tokens_company (company_id),
    INDEX idx_api_tokens_hash (token_hash),
    CONSTRAINT fk_api_tokens_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Feature Flags (per-tenant feature toggles) ───
CREATE TABLE IF NOT EXISTS feature_flags (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    feature         VARCHAR(50) NOT NULL,
    enabled         TINYINT(1) DEFAULT 1,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_ff_company_feature (company_id, feature),
    CONSTRAINT fk_ff_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Webhooks (tenant webhook registrations) ───
CREATE TABLE IF NOT EXISTS webhooks (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    url             VARCHAR(500) NOT NULL,
    secret          VARCHAR(64) NOT NULL,
    events          JSON DEFAULT NULL,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_webhooks_company (company_id, is_active),
    CONSTRAINT fk_webhooks_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Webhook Deliveries (delivery log for debugging) ───
CREATE TABLE IF NOT EXISTS webhook_deliveries (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webhook_id      INT UNSIGNED NOT NULL,
    event           VARCHAR(50) NOT NULL,
    payload         TEXT,
    response_code   SMALLINT DEFAULT NULL,
    response_body   TEXT DEFAULT NULL,
    success         TINYINT(1) DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_wd_webhook (webhook_id, created_at),
    CONSTRAINT fk_wd_webhook FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Background Jobs (async queue table) ───
CREATE TABLE IF NOT EXISTS jobs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue           VARCHAR(50) DEFAULT 'default',
    payload         JSON NOT NULL,
    attempts        TINYINT UNSIGNED DEFAULT 0,
    max_attempts    TINYINT UNSIGNED DEFAULT 3,
    status          ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    company_id      INT UNSIGNED DEFAULT NULL,
    reserved_at     TIMESTAMP NULL DEFAULT NULL,
    completed_at    TIMESTAMP NULL DEFAULT NULL,
    failed_at       TIMESTAMP NULL DEFAULT NULL,
    last_error      TEXT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_jobs_queue_status (queue, status, created_at),
    INDEX idx_jobs_company (company_id),
    CONSTRAINT fk_jobs_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tenant Usage Analytics ───
CREATE TABLE IF NOT EXISTS tenant_usage (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    metric          VARCHAR(50) NOT NULL,
    value           DECIMAL(15,2) DEFAULT 0,
    period          DATE NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_usage_company_metric_period (company_id, metric, period),
    CONSTRAINT fk_usage_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Pre-Aggregated Report Tables (avoid heavy JOINs on each report load) ───
CREATE TABLE IF NOT EXISTS daily_sales_summary (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    sale_date       DATE NOT NULL,
    total_sales     INT UNSIGNED DEFAULT 0,
    total_revenue   DECIMAL(15,2) DEFAULT 0,
    total_tax       DECIMAL(15,2) DEFAULT 0,
    total_discount  DECIMAL(15,2) DEFAULT 0,
    total_items     INT UNSIGNED DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_dss_company_date (company_id, sale_date),
    CONSTRAINT fk_dss_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Verification ───
SELECT 'Enterprise platform tables' AS status, COUNT(*) AS count 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (
    'api_tokens', 'feature_flags', 'webhooks', 'webhook_deliveries', 
    'jobs', 'tenant_usage', 'daily_sales_summary'
);
