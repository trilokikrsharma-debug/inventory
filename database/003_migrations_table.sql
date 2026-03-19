-- ============================================================
-- InvenBill Pro — Migration Version Tracking
-- 
-- PURPOSE: Track which SQL migrations have been executed.
-- This table MUST be created first before the migration runner.
-- ============================================================

CREATE TABLE IF NOT EXISTS `migrations` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `filename`    VARCHAR(255) NOT NULL,
    `batch`       INT UNSIGNED NOT NULL DEFAULT 1,
    `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_migration_filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
