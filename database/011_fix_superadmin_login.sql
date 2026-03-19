-- ============================================================================
-- InvenBill Pro — Fix Super Admin Login (Run ONCE)
-- ============================================================================
-- This script ensures:
--   1. The platform_admin role (id=100) exists with is_super_admin=1
--   2. The admin user triloki@tsalegacy.shop has:
--      - A properly hashed password
--      - role_id = 100
--      - is_super_admin = 1
--      - is_active = 1
--      - deleted_at = NULL
-- ============================================================================

-- Step 1: Ensure role_id=100 (platform_admin) exists
INSERT IGNORE INTO `roles` (`id`, `name`, `display_name`, `description`, `is_super_admin`, `is_system`)
VALUES (100, 'platform_admin', 'Platform Super Admin', 'Platform owner — full system control. NEVER assign via UI.', 1, 1);

-- Step 2: Ensure role_id=100 has all permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 100, id FROM `permissions`;

-- Step 3: Fix the super-admin user record
-- Password = Triloki@2017 (bcrypt hashed with PASSWORD_DEFAULT)
UPDATE `users` SET
    `password`       = '$2y$10$VkU.Qrbs9ql63cip6WuCieS9RbmUSe2VtXUpwMameEi5xNFygmheW',
    `role_id`        = 100,
    `is_super_admin` = 1,
    `is_active`      = 1,
    `deleted_at`     = NULL
WHERE `email` = 'triloki@tsalegacy.shop';

-- Step 4: Verify (run this SELECT after the UPDATE to confirm)
-- SELECT id, username, email, role_id, is_super_admin, is_active, company_id, deleted_at,
--        LEFT(password, 7) as password_prefix
-- FROM users WHERE email = 'triloki@tsalegacy.shop';
-- Expected: role_id=100, is_super_admin=1, is_active=1, password starts with $2y$10$

-- Step 5: Ensure role_id=1 (tenant admin) does NOT have is_super_admin=1
-- This prevents signup users from getting platform access
UPDATE `roles` SET `is_super_admin` = 0
WHERE `id` = 1 AND `name` = 'admin';

-- ============================================================================
-- DONE. The admin should now be able to login with:
--   Email: triloki@tsalegacy.shop
--   Password: Triloki@2017
-- And will be redirected to: ?page=platform&action=dashboard
-- ============================================================================
