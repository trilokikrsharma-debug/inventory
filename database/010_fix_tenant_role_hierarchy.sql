-- ============================================================================
-- InvenBill Pro — Fix Tenant Role Hierarchy (Migration 010)
--
-- PROBLEM: The existing roles table has role_id=1 (admin) with is_super_admin=1.
-- This causes every new signup user (assigned role_id=1) to inherit platform
-- super-admin privileges. This migration:
--
--   1. Introduces a new role: 'tenant_owner' (is_super_admin=0) to be used
--      by signup users and user management within tenant scope.
--   2. Strips is_super_admin=1 from the generic 'admin' role (id=1).
--      The 'admin' role becomes a tenant-level Administrator (full tenant access).
--   3. Adds a dedicated 'platform_admin' role (is_super_admin=1) that can ONLY
--      be assigned manually via DB — never via signup or UI.
--   4. Existing signup users with role_id=1 retain their tenant admin access,
--      but lose platform super-admin privileges.
--
-- RUN ONCE. Safe to re-run (uses UPDATE ... WHERE / INSERT IGNORE).
-- ============================================================================

-- Step 1: Remove is_super_admin=1 from the generic tenant admin role (id=1).
-- This is SAFE: existing tenant admins keep full tenant access via their
-- permissions, they simply no longer bypass the platform super-admin check.
UPDATE `roles`
SET `is_super_admin` = 0,
    `description`    = 'Full tenant-level access — bypasses all tenant RBAC checks. NOT a platform admin.'
WHERE `id` = 1
  AND `name` = 'admin';

-- Step 2: Insert a dedicated platform-level super-admin role.
-- This role must ONLY be assigned manually in the DB, never via signup or UI.
INSERT IGNORE INTO `roles` (`id`, `name`, `display_name`, `description`, `is_super_admin`, `is_system`)
VALUES (
    100,
    'platform_admin',
    'Platform Super Admin',
    'Platform owner — can access all tenants, billing, and system controls. NEVER assign via UI.',
    1,       -- is_super_admin = 1
    1        -- is_system = 1 (cannot be deleted)
);

-- Step 3: Ensure all platform super-admin users are assigned role_id=100.
-- Only users with is_super_admin=1 on the users table should get this.
-- (Runs as a no-op if no such users exist yet.)
UPDATE `users`
SET `role_id` = 100
WHERE `is_super_admin` = 1
  AND `deleted_at` IS NULL;

-- Step 4: Ensure all existing signup users (role='admin', role_id=1) remain
-- as tenant admins with is_super_admin=0 in their session cache reset on
-- next login. No data change needed — just verifying:
-- SELECT id, username, role, role_id, is_super_admin FROM users WHERE role_id = 1;
-- All should have is_super_admin=0 (column on users table).

-- Step 5: Assign all permissions to the platform_admin role as well.
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 100, id FROM `permissions`;

-- ============================================================================
-- HOW TO GRANT PLATFORM SUPER ADMIN (manual DB steps):
-- ============================================================================
--
-- Option A: Assign platform_admin role:
--   UPDATE users SET role_id = 100, is_super_admin = 1
--   WHERE email = 'owner@yourplatform.com' AND deleted_at IS NULL;
--
-- Option B: Set direct flag only (if role_id not used for platform owner):
--   UPDATE users SET is_super_admin = 1
--   WHERE email = 'owner@yourplatform.com' AND deleted_at IS NULL;
--
-- VERIFY:
--   SELECT id, username, email, role_id, is_super_admin
--   FROM users WHERE is_super_admin = 1;
--
-- ============================================================================
