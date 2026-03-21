-- ============================================================
-- MULTI-TENANT SaaS MIGRATION (MySQL 8 + PDO SPLIT SAFE)
-- Converts single-tenant schema to shared-database multi-tenant
--
-- SAFETY:
-- - No dynamic SQL or routine blocks
-- - No CREATE/DROP INDEX IF [NOT] EXISTS
-- - Additive schema changes and controlled unique-key conversion
-- ============================================================

-- NOTE:
-- Database selection is handled by cli/migrate.php using configured DB_NAME.
-- This migration is intended to run once (tracked in migrations table).

-- ============================================================
-- 1) CORE TENANT TABLE
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

INSERT IGNORE INTO `companies` (`id`, `name`, `slug`, `plan`, `status`)
VALUES (1, 'My Business', 'my-business', 'pro', 'active');

-- ============================================================
-- 2) ADD company_id TO BUSINESS TABLES
-- ============================================================
ALTER TABLE `company_settings`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `users`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `categories`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `brands`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `units`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `products`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `stock_history`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `customers`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `suppliers`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `purchases`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `purchase_items`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `purchase_returns`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `purchase_return_items`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `sales`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `sale_items`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `sale_returns`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `sale_return_items`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `payments`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `activity_log`
  ADD COLUMN `company_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;

-- ============================================================
-- 3) DATA BACKFILL
-- ============================================================
UPDATE `company_settings`
SET `company_id` = 1
WHERE `company_id` = 0 OR `company_id` IS NULL;

UPDATE `users`
SET `company_id` = 1
WHERE `company_id` = 0 OR `company_id` IS NULL;

UPDATE `companies`
SET `owner_user_id` = (
  SELECT MIN(`id`) FROM `users` WHERE `role` = 'admin' AND `deleted_at` IS NULL
)
WHERE `id` = 1 AND `owner_user_id` IS NULL;

-- ============================================================
-- 4) INDEXES (DIRECT SQL FOR PDO COMPATIBILITY)
-- ============================================================
ALTER TABLE `company_settings`
  ADD INDEX `idx_cs_company` (`company_id`);
ALTER TABLE `users`
  ADD INDEX `idx_users_company` (`company_id`);
ALTER TABLE `categories`
  ADD INDEX `idx_categories_company` (`company_id`);
ALTER TABLE `brands`
  ADD INDEX `idx_brands_company` (`company_id`);
ALTER TABLE `units`
  ADD INDEX `idx_units_company` (`company_id`);
ALTER TABLE `products`
  ADD INDEX `idx_products_company` (`company_id`);
ALTER TABLE `stock_history`
  ADD INDEX `idx_stockh_company` (`company_id`);
ALTER TABLE `customers`
  ADD INDEX `idx_customers_company` (`company_id`);
ALTER TABLE `suppliers`
  ADD INDEX `idx_suppliers_company` (`company_id`);
ALTER TABLE `purchases`
  ADD INDEX `idx_purchases_company` (`company_id`);
ALTER TABLE `purchase_items`
  ADD INDEX `idx_pitems_company` (`company_id`);
ALTER TABLE `purchase_returns`
  ADD INDEX `idx_preturn_company` (`company_id`);
ALTER TABLE `purchase_return_items`
  ADD INDEX `idx_pritems_company` (`company_id`);
ALTER TABLE `sales`
  ADD INDEX `idx_sales_company` (`company_id`);
ALTER TABLE `sale_items`
  ADD INDEX `idx_sitems_company` (`company_id`);
ALTER TABLE `sale_returns`
  ADD INDEX `idx_sreturn_company` (`company_id`);
ALTER TABLE `sale_return_items`
  ADD INDEX `idx_sritems_company` (`company_id`);
ALTER TABLE `payments`
  ADD INDEX `idx_payments_company` (`company_id`);
ALTER TABLE `activity_log`
  ADD INDEX `idx_activity_company` (`company_id`);

-- Tenant-scoped unique constraints
-- NOTE: these DROP statements are safe in normal upgrade path from schema.sql.
ALTER TABLE `products`
  DROP INDEX `sku`;
ALTER TABLE `products`
  ADD UNIQUE INDEX `uq_products_company_sku` (`company_id`, `sku`);
ALTER TABLE `sales`
  DROP INDEX `invoice_number`;
ALTER TABLE `sales`
  ADD UNIQUE INDEX `uq_sales_company_invoice` (`company_id`, `invoice_number`);
ALTER TABLE `purchases`
  DROP INDEX `invoice_number`;
ALTER TABLE `purchases`
  ADD UNIQUE INDEX `uq_purchases_company_invoice` (`company_id`, `invoice_number`);
ALTER TABLE `payments`
  DROP INDEX `payment_number`;
ALTER TABLE `payments`
  ADD UNIQUE INDEX `uq_payments_company_number` (`company_id`, `payment_number`);
ALTER TABLE `users`
  DROP INDEX `username`;
ALTER TABLE `users`
  ADD UNIQUE INDEX `uq_users_company_username` (`company_id`, `username`);

-- Tenant query performance indexes
ALTER TABLE `products`
  ADD INDEX `idx_products_tenant_active` (`company_id`, `is_active`, `deleted_at`);
ALTER TABLE `sales`
  ADD INDEX `idx_sales_tenant_date` (`company_id`, `sale_date`, `deleted_at`);
ALTER TABLE `purchases`
  ADD INDEX `idx_purchases_tenant_date` (`company_id`, `purchase_date`, `deleted_at`);
ALTER TABLE `payments`
  ADD INDEX `idx_payments_tenant_date` (`company_id`, `payment_date`, `deleted_at`);
ALTER TABLE `customers`
  ADD INDEX `idx_customers_tenant_balance` (`company_id`, `current_balance`, `deleted_at`);
ALTER TABLE `stock_history`
  ADD INDEX `idx_stockh_tenant_product` (`company_id`, `product_id`, `created_at`);
ALTER TABLE `sale_items`
  ADD INDEX `idx_sitems_tenant_product` (`company_id`, `product_id`);
ALTER TABLE `activity_log`
  ADD INDEX `idx_activity_tenant_date` (`company_id`, `created_at`);

-- ============================================================
-- 5) NOTE ON QUOTATIONS
-- ============================================================
-- Quotations/quotation_items are intentionally not altered here because
-- quotations.sql runs later in migration order and already contains company_id.

-- ============================================================
-- 6) DEMO TENANT
-- ============================================================
INSERT IGNORE INTO `companies`
  (`id`, `name`, `slug`, `plan`, `status`, `is_demo`, `max_users`, `max_products`)
VALUES
  (999, 'InvenBill Demo Store', 'demo-store', 'pro', 'active', 1, 99, 9999);

-- ============================================================
-- MIGRATION COMPLETE
-- ============================================================
