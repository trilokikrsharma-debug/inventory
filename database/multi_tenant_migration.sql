-- ============================================================
-- MULTI-TENANT SaaS MIGRATION
-- Converts single-tenant schema to shared-database multi-tenant
-- 
-- SAFE: All operations are additive (CREATE TABLE, ALTER TABLE ADD COLUMN)
-- IDEMPOTENT: Uses IF NOT EXISTS / conditional checks
-- BACKWARD COMPATIBLE: Existing data assigned to company_id = 1
-- ============================================================

-- NOTE:
-- Database selection is handled by cli/migrate.php using configured DB_NAME.

-- ============================================================
-- 1. CREATE COMPANIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `companies` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-safe unique identifier',
  `owner_user_id` INT UNSIGNED DEFAULT NULL COMMENT 'User who created the company',
  `plan` ENUM('starter','growth','pro') NOT NULL DEFAULT 'starter',
  `status` ENUM('active','suspended','cancelled') NOT NULL DEFAULT 'active',
  `is_demo` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = demo company, write-protected',
  `max_users` INT UNSIGNED DEFAULT 3 COMMENT 'Max users allowed by plan',
  `max_products` INT UNSIGNED DEFAULT 500 COMMENT 'Max products allowed by plan',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_companies_status` (`status`),
  INDEX `idx_companies_plan` (`plan`),
  INDEX `idx_companies_owner` (`owner_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. INSERT DEFAULT COMPANY (for existing data migration)
-- ============================================================
INSERT IGNORE INTO `companies` (`id`, `name`, `slug`, `plan`, `status`)
VALUES (1, 'My Business', 'my-business', 'pro', 'active');

-- ============================================================
-- 3. ADD company_id TO ALL BUSINESS TABLES
-- Each block checks if column already exists (idempotent)
-- ============================================================

-- 3.1 company_settings
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_settings' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `company_settings` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_cs_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.2 users
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `users` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_users_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.3 categories
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `categories` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_categories_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.4 brands
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'brands' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `brands` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_brands_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.5 units
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'units' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `units` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_units_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.6 products
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `products` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_products_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.7 stock_history
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_history' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `stock_history` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_stockh_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.8 customers
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `customers` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_customers_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.9 suppliers
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `suppliers` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_suppliers_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.10 purchases
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `purchases` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_purchases_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.11 purchase_items
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_items' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `purchase_items` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_pitems_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.12 purchase_returns
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_returns' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `purchase_returns` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_preturn_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.13 purchase_return_items
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_return_items' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `purchase_return_items` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_pritems_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.14 sales
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `sales` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_sales_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.15 sale_items
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sale_items' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `sale_items` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_sitems_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.16 sale_returns
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sale_returns' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `sale_returns` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_sreturn_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.17 sale_return_items
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sale_return_items' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `sale_return_items` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_sritems_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.18 payments
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `payments` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_payments_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.19 activity_log
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_log' AND COLUMN_NAME = 'company_id');
SET @sql = IF(@col = 0, 
    'ALTER TABLE `activity_log` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_activity_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.20 quotations (if exists)
SET @tbl = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quotations');
SET @col = IF(@tbl > 0, (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quotations' AND COLUMN_NAME = 'company_id'), 1);
SET @sql = IF(@tbl > 0 AND @col = 0, 
    'ALTER TABLE `quotations` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_quotations_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3.21 quotation_items (if exists)
SET @tbl = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quotation_items');
SET @col = IF(@tbl > 0, (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quotation_items' AND COLUMN_NAME = 'company_id'), 1);
SET @sql = IF(@tbl > 0 AND @col = 0, 
    'ALTER TABLE `quotation_items` ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_qitems_company` (`company_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 4. UPDATE EXISTING DATA — ASSIGN TO COMPANY 1
-- ============================================================
UPDATE `company_settings` SET `company_id` = 1 WHERE `company_id` = 0 OR `company_id` IS NULL;
-- Link company to its owner (the first admin user)
UPDATE `companies` SET `owner_user_id` = (SELECT MIN(id) FROM `users` WHERE `role` = 'admin' AND `deleted_at` IS NULL) WHERE `id` = 1 AND `owner_user_id` IS NULL;

-- ============================================================
-- 5. COMPANY-SCOPED UNIQUE INDEXES
-- Drop old global UNIQUE and recreate as company-scoped
-- ============================================================

-- 5.1 products.sku — unique per company
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND INDEX_NAME = 'sku');
SET @sql = IF(@idx > 0, 'ALTER TABLE `products` DROP INDEX `sku`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND INDEX_NAME = 'uq_products_company_sku');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `products` ADD UNIQUE INDEX `uq_products_company_sku` (`company_id`, `sku`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5.2 sales.invoice_number — unique per company
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales' AND INDEX_NAME = 'invoice_number');
SET @sql = IF(@idx > 0, 'ALTER TABLE `sales` DROP INDEX `invoice_number`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales' AND INDEX_NAME = 'uq_sales_company_invoice');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `sales` ADD UNIQUE INDEX `uq_sales_company_invoice` (`company_id`, `invoice_number`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5.3 purchases.invoice_number — unique per company
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND INDEX_NAME = 'invoice_number');
SET @sql = IF(@idx > 0, 'ALTER TABLE `purchases` DROP INDEX `invoice_number`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND INDEX_NAME = 'uq_purchases_company_invoice');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `purchases` ADD UNIQUE INDEX `uq_purchases_company_invoice` (`company_id`, `invoice_number`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5.4 payments.payment_number — unique per company
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND INDEX_NAME = 'payment_number');
SET @sql = IF(@idx > 0, 'ALTER TABLE `payments` DROP INDEX `payment_number`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND INDEX_NAME = 'uq_payments_company_number');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `payments` ADD UNIQUE INDEX `uq_payments_company_number` (`company_id`, `payment_number`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5.5 users.username — unique per company (allow same username in different companies)
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'username');
SET @sql = IF(@idx > 0, 'ALTER TABLE `users` DROP INDEX `username`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_company_username');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `users` ADD UNIQUE INDEX `uq_users_company_username` (`company_id`, `username`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5.6 users.email — keep globally unique (for login across companies)
-- No change needed — email remains globally unique

-- ============================================================
-- 6. PERFORMANCE COMPOUND INDEXES
-- ============================================================
-- These indexes optimize the most common tenant-scoped queries

-- Products: company + active + deleted (most common listing query)
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND INDEX_NAME = 'idx_products_tenant_active');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `products` ADD INDEX `idx_products_tenant_active` (`company_id`, `is_active`, `deleted_at`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Sales: company + date range
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales' AND INDEX_NAME = 'idx_sales_tenant_date');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `sales` ADD INDEX `idx_sales_tenant_date` (`company_id`, `sale_date`, `deleted_at`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Purchases: company + date range
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND INDEX_NAME = 'idx_purchases_tenant_date');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `purchases` ADD INDEX `idx_purchases_tenant_date` (`company_id`, `purchase_date`, `deleted_at`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Payments: company + date range
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND INDEX_NAME = 'idx_payments_tenant_date');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `payments` ADD INDEX `idx_payments_tenant_date` (`company_id`, `payment_date`, `deleted_at`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Customers: company + balance (for dues queries)
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_customers_tenant_balance');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `customers` ADD INDEX `idx_customers_tenant_balance` (`company_id`, `current_balance`, `deleted_at`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Stock history: company + product
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_history' AND INDEX_NAME = 'idx_stockh_tenant_product');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `stock_history` ADD INDEX `idx_stockh_tenant_product` (`company_id`, `product_id`, `created_at`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Sale items: company + product (for top products query)
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sale_items' AND INDEX_NAME = 'idx_sitems_tenant_product');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `sale_items` ADD INDEX `idx_sitems_tenant_product` (`company_id`, `product_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Activity log: company + date
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_log' AND INDEX_NAME = 'idx_activity_tenant_date');
SET @sql = IF(@idx = 0, 
    'ALTER TABLE `activity_log` ADD INDEX `idx_activity_tenant_date` (`company_id`, `created_at`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 7. INSERT DEMO COMPANY
-- ============================================================
INSERT IGNORE INTO `companies` (`id`, `name`, `slug`, `plan`, `status`, `is_demo`, `max_users`, `max_products`)
VALUES (999, 'InvenBill Demo Store', 'demo-store', 'pro', 'active', 1, 99, 9999);

-- ============================================================
-- MIGRATION COMPLETE
-- ============================================================
