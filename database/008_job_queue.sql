-- ============================================================
-- InvenBill Pro — Background Job Queue
-- 
-- PURPOSE: Lightweight job queue for background processing.
-- Used for: backup processing, email notifications, 
-- webhook delivery, report generation.
-- ============================================================

CREATE TABLE IF NOT EXISTS `jobs` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id`    INT UNSIGNED NOT NULL,
    `queue`         VARCHAR(50) NOT NULL DEFAULT 'default',
    `handler`       VARCHAR(255) NOT NULL COMMENT 'Fully qualified class or callable name',
    `payload`       JSON NOT NULL COMMENT 'Serialized job data',
    `status`        ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    `attempts`      TINYINT UNSIGNED DEFAULT 0,
    `max_attempts`  TINYINT UNSIGNED DEFAULT 3,
    `error`         TEXT NULL COMMENT 'Last error message',
    `priority`      TINYINT UNSIGNED DEFAULT 5 COMMENT '1=highest, 10=lowest',
    `scheduled_at`  TIMESTAMP NULL DEFAULT NULL COMMENT 'NULL = run immediately',
    `started_at`    TIMESTAMP NULL DEFAULT NULL,
    `completed_at`  TIMESTAMP NULL DEFAULT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_jobs_pending`  (`status`, `queue`, `priority`, `scheduled_at`),
    INDEX `idx_jobs_company`  (`company_id`, `status`),
    INDEX `idx_jobs_cleanup`  (`status`, `completed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
