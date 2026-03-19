-- ============================================================================
-- InvenBill Pro — Add company_id to roles table for tenant scoping
-- Migration: 012_roles_tenant_scoping.sql
-- 
-- PURPOSE: Enable tenant-scoped role management.
--   - System-level roles (platform_admin, default admin) have company_id = NULL
--   - Tenant-created roles have company_id = their tenant's company ID
--   - This prevents cross-tenant role visibility (RBAC-1 fix)
--
-- SAFE TO RE-RUN: Uses IF NOT EXISTS / UPDATE with WHERE conditions.
-- ============================================================================

-- Step 1: Add company_id column to roles table (NULL = system-level role)
-- Check if column exists first to make this idempotent
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'roles' 
      AND COLUMN_NAME = 'company_id'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `roles` ADD COLUMN `company_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: Add index for tenant-scoped queries
CREATE INDEX IF NOT EXISTS `idx_roles_company` ON `roles`(`company_id`);

-- Step 3: Ensure system-level roles remain company_id = NULL
-- (platform_admin and default system roles should be global)
UPDATE `roles` SET `company_id` = NULL WHERE `is_system` = 1;
UPDATE `roles` SET `company_id` = NULL WHERE `is_super_admin` = 1;

-- Step 4: Add FK constraint (optional but recommended)
-- ALTER TABLE `roles` ADD CONSTRAINT `fk_roles_company`
--   FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`)
--   ON DELETE CASCADE ON UPDATE CASCADE;
-- NOTE: Uncomment above if you want strict FK enforcement.
--       Requires that all existing company_id values in roles have valid companies.

-- ============================================================================
-- DONE. The 'roles' table now supports tenant scoping:
--   - company_id = NULL  →  system/global role (visible to all tenants)
--   - company_id = X     →  belongs to tenant X (visible only to tenant X)
-- ============================================================================
