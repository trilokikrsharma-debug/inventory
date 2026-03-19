-- ============================================================
-- InvenBill Pro — Immutable Audit Trail
-- 
-- PURPOSE: Financial-grade audit logging for all changes
-- to critical tables (sales, purchases, payments, etc.)
--
-- This table is INSERT-ONLY — no updates or deletes allowed.
-- Use repository hooks (AuditService) to populate it.
-- ============================================================

CREATE TABLE IF NOT EXISTS `audit_trail` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id`    INT UNSIGNED NOT NULL,
    `table_name`    VARCHAR(64) NOT NULL,
    `record_id`     INT UNSIGNED NOT NULL,
    `action`        ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    `old_values`    JSON NULL COMMENT 'Previous state (NULL for INSERT)',
    `new_values`    JSON NULL COMMENT 'New state (NULL for DELETE)',
    `changed_by`    INT UNSIGNED NULL COMMENT 'User ID who made the change',
    `ip_address`    VARCHAR(45) NULL COMMENT 'Request IP at time of change',
    `changed_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Query indexes
    INDEX `idx_audit_company_table` (`company_id`, `table_name`, `record_id`),
    INDEX `idx_audit_company_date`  (`company_id`, `changed_at`),
    INDEX `idx_audit_table_record`  (`table_name`, `record_id`),
    INDEX `idx_audit_user`          (`changed_by`),
    INDEX `idx_audit_date`          (`changed_at`),

    -- Foreign keys
    CONSTRAINT `fk_audit_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
