-- ============================================================
-- RBAC Migration — Phase 1
-- Run this migration ONCE on an existing database.
-- Safe: All operations are additive (CREATE TABLE, ALTER TABLE ADD COLUMN).
-- Idempotent: Uses IF NOT EXISTS where possible.
-- ============================================================

-- 1. ROLES TABLE
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `display_name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_super_admin` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = bypasses all permission checks',
  `is_system` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = cannot be deleted',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. PERMISSIONS TABLE
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'e.g. sales.create',
  `display_name` VARCHAR(150) NOT NULL COMMENT 'e.g. Create Sales',
  `module` VARCHAR(50) NOT NULL COMMENT 'e.g. sales, purchases',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_perm_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ROLE-PERMISSION PIVOT TABLE
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ADD role_id TO USERS (nullable, keeps existing role ENUM untouched)
-- Check if column exists first to make this idempotent
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `users` ADD COLUMN `role_id` INT UNSIGNED DEFAULT NULL AFTER `role`, ADD INDEX `idx_users_role_id` (`role_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- SEED DEFAULT ROLES
-- ============================================================
INSERT IGNORE INTO `roles` (`id`, `name`, `display_name`, `description`, `is_super_admin`, `is_system`) VALUES
  (1, 'admin',      'Administrator', 'Full system access — bypasses all permission checks', 1, 1),
  (2, 'manager',    'Manager',       'Manages sales, purchases, reports, and staff',        0, 0),
  (3, 'accountant', 'Accountant',    'Manages payments, receipts, and financial reports',    0, 0),
  (4, 'cashier',    'Cashier',       'Creates sales and receipts, views products',           0, 0),
  (5, 'staff',      'Staff',         'Basic view-only access for most modules',              0, 1);

-- ============================================================
-- SEED PERMISSIONS (37 total across 13 modules)
-- ============================================================
INSERT IGNORE INTO `permissions` (`name`, `display_name`, `module`) VALUES
  -- Dashboard
  ('dashboard.view',      'View Dashboard',         'dashboard'),
  -- Sales
  ('sales.view',          'View Sales',             'sales'),
  ('sales.create',        'Create Sales',           'sales'),
  ('sales.edit',          'Edit Sales',             'sales'),
  ('sales.delete',        'Delete Sales',           'sales'),
  -- Purchases
  ('purchases.view',      'View Purchases',         'purchases'),
  ('purchases.create',    'Create Purchases',       'purchases'),
  ('purchases.edit',      'Edit Purchases',         'purchases'),
  ('purchases.delete',    'Delete Purchases',       'purchases'),
  -- Payments
  ('payments.view',       'View Payments',          'payments'),
  ('payments.create',     'Create Payments',        'payments'),
  ('payments.delete',     'Delete Payments',        'payments'),
  -- Products
  ('products.view',       'View Products',          'products'),
  ('products.create',     'Create Products',        'products'),
  ('products.edit',       'Edit Products',          'products'),
  ('products.delete',     'Delete Products',        'products'),
  -- Customers
  ('customers.view',      'View Customers',         'customers'),
  ('customers.create',    'Create Customers',       'customers'),
  ('customers.edit',      'Edit Customers',         'customers'),
  ('customers.delete',    'Delete Customers',       'customers'),
  -- Suppliers
  ('suppliers.view',      'View Suppliers',         'suppliers'),
  ('suppliers.create',    'Create Suppliers',       'suppliers'),
  ('suppliers.edit',      'Edit Suppliers',         'suppliers'),
  ('suppliers.delete',    'Delete Suppliers',       'suppliers'),
  -- Quotations
  ('quotations.view',     'View Quotations',        'quotations'),
  ('quotations.create',   'Create Quotations',      'quotations'),
  ('quotations.convert',  'Convert Quotation to Sale', 'quotations'),
  ('quotations.delete',   'Delete Quotations',      'quotations'),
  -- Sale Returns
  ('returns.view',        'View Sale Returns',      'returns'),
  ('returns.create',      'Create Sale Returns',    'returns'),
  -- Reports
  ('reports.view',        'View Reports',           'reports'),
  -- Catalog (Categories, Brands, Units)
  ('catalog.manage',      'Manage Categories/Brands/Units', 'catalog'),
  -- Users
  ('users.view',          'View Users',             'users'),
  ('users.create',        'Create Users',           'users'),
  ('users.edit',          'Edit Users',             'users'),
  ('users.delete',        'Delete Users',           'users'),
  -- Settings
  ('settings.manage',     'Manage Settings',        'settings'),
  -- Backup
  ('backup.manage',       'Backup & Restore',       'backup'),
  -- Roles
  ('roles.manage',        'Manage Roles & Permissions', 'roles');

-- ============================================================
-- ASSIGN DEFAULT PERMISSIONS TO ROLES
-- ============================================================

-- Helper: Get permission IDs by name
-- Admin (is_super_admin=1, bypasses checks, but still assign all for reference)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Manager: broad access except delete, users, settings, backup
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` WHERE `name` IN (
  'dashboard.view',
  'sales.view', 'sales.create', 'sales.edit',
  'purchases.view', 'purchases.create', 'purchases.edit',
  'payments.view', 'payments.create',
  'products.view', 'products.create', 'products.edit',
  'customers.view', 'customers.create', 'customers.edit',
  'suppliers.view', 'suppliers.create', 'suppliers.edit',
  'quotations.view', 'quotations.create', 'quotations.convert',
  'returns.view', 'returns.create',
  'reports.view',
  'catalog.manage'
);

-- Accountant: financial focus
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` WHERE `name` IN (
  'dashboard.view',
  'sales.view',
  'purchases.view',
  'payments.view', 'payments.create',
  'customers.view',
  'suppliers.view',
  'returns.view',
  'reports.view'
);

-- Cashier: sales and receipts
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions` WHERE `name` IN (
  'dashboard.view',
  'sales.view', 'sales.create',
  'payments.view', 'payments.create',
  'products.view',
  'customers.view', 'customers.create',
  'quotations.view', 'quotations.create'
);

-- Staff: view-only basics
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM `permissions` WHERE `name` IN (
  'dashboard.view',
  'sales.view',
  'purchases.view',
  'products.view',
  'customers.view',
  'suppliers.view'
);

-- ============================================================
-- MAP EXISTING USERS TO ROLES
-- ============================================================
UPDATE `users` SET `role_id` = 1 WHERE `role` = 'admin' AND `role_id` IS NULL;
UPDATE `users` SET `role_id` = 5 WHERE `role` = 'staff' AND `role_id` IS NULL;
-- Handle any 'user' role values that might exist
UPDATE `users` SET `role_id` = 5 WHERE `role_id` IS NULL AND `deleted_at` IS NULL;
