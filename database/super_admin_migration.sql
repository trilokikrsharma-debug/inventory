-- ============================================================================
-- InvenBill Pro — Super Admin Setup Migration
-- 
-- This migration adds a direct is_super_admin column to the users table
-- as a defense-in-depth measure. The primary super-admin check is still
-- via the roles.is_super_admin flag, but this column provides:
--  1. A fast, direct DB-level check without JOINing roles
--  2. An independent flag that cannot be changed by role reassignment alone
--  3. An audit trail for who has platform-level access
--
-- RUN THIS ONLY ONCE. Safe to run multiple times (uses IF NOT EXISTS logic).
-- ============================================================================

-- Step 1: Add is_super_admin to users table (if not exists)
-- This is a defense-in-depth column; the primary check remains via roles
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'is_super_admin'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN is_super_admin TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''Platform super admin flag - cannot be changed via UI'' AFTER role_id',
    'SELECT ''Column already exists'' AS status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: Index for fast super-admin lookups (optional, for admin panels)
-- Only useful if you need to list all super-admins quickly
-- CREATE INDEX idx_users_super_admin ON users (is_super_admin) WHERE is_super_admin = 1;
-- MySQL doesn't support partial indexes, so we skip this.

-- ============================================================================
-- PROMOTING A USER TO SUPER ADMIN
-- ============================================================================
-- 
-- Option A: Via roles (RECOMMENDED — primary method)
-- Assign the user to a role that has is_super_admin = 1
--
--   UPDATE users SET role_id = (SELECT id FROM roles WHERE is_super_admin = 1 LIMIT 1)
--   WHERE email = 'admin@example.com' AND deleted_at IS NULL;
--
-- Option B: Direct flag (defense-in-depth, optional)
-- Only use this if you want a user to retain super-admin even if their role changes
--
--   UPDATE users SET is_super_admin = 1
--   WHERE email = 'admin@example.com' AND deleted_at IS NULL;
--
-- VERIFY after promotion:
--   SELECT id, username, email, role_id, is_super_admin
--   FROM users WHERE is_super_admin = 1 OR role_id IN (SELECT id FROM roles WHERE is_super_admin = 1);
--
-- REVOKING super-admin:
--   UPDATE users SET is_super_admin = 0 WHERE email = 'admin@example.com';
--   -- AND reassign to a non-super-admin role:
--   UPDATE users SET role_id = 5 WHERE email = 'admin@example.com'; -- role_id=5 is Staff
--
-- ============================================================================
-- SECURITY NOTES
-- ============================================================================
--
-- 1. NEVER auto-create super admin users in code
-- 2. NEVER allow is_super_admin to be set via POST/GET/form submission
-- 3. ONLY set via direct database access (MySQL CLI, phpMyAdmin, migration)
-- 4. Limit super-admin accounts to 1-2 platform owners
-- 5. Log all super-admin actions to activity_log
-- 6. Periodically audit: SELECT * FROM users WHERE is_super_admin = 1;
-- ============================================================================
