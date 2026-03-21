-- ============================================================
-- Inventory & Billing Management System - Database Schema
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================

-- NOTE:
-- Database selection is handled by cli/migrate.php using configured DB_NAME.
-- Do not hardcode CREATE DATABASE/USE in migration files.


-- ============================================================
-- 1. COMPANY SETTINGS
-- ============================================================
CREATE TABLE `company_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `company_name` VARCHAR(255) NOT NULL DEFAULT 'My Company',
  `company_email` VARCHAR(255) DEFAULT NULL,
  `company_phone` VARCHAR(50) DEFAULT NULL,
  `company_address` TEXT DEFAULT NULL,
  `company_city` VARCHAR(100) DEFAULT NULL,
  `company_state` VARCHAR(100) DEFAULT NULL,
  `company_zip` VARCHAR(20) DEFAULT NULL,
  `company_country` VARCHAR(100) DEFAULT 'India',
  `company_logo` VARCHAR(255) DEFAULT NULL,
  `company_website` VARCHAR(255) DEFAULT NULL,
  `tax_number` VARCHAR(100) DEFAULT NULL COMMENT 'GST / VAT Number',
  `currency_symbol` VARCHAR(10) DEFAULT '₹',
  `currency_code` VARCHAR(10) DEFAULT 'INR',
  `date_format` VARCHAR(20) DEFAULT 'd-m-Y',
  `timezone` VARCHAR(50) DEFAULT 'Asia/Kolkata',
  `invoice_prefix` VARCHAR(20) DEFAULT 'INV-',
  `purchase_prefix` VARCHAR(20) DEFAULT 'PUR-',
  `payment_prefix` VARCHAR(20) DEFAULT 'PAY-',
  `receipt_prefix` VARCHAR(20) DEFAULT 'RCP-',
  `invoice_next_number` INT UNSIGNED DEFAULT 1,
  `purchase_next_number` INT UNSIGNED DEFAULT 1,
  `payment_next_number` INT UNSIGNED DEFAULT 1,
  `receipt_next_number` INT UNSIGNED DEFAULT 1,
  `enable_tax` TINYINT(1) DEFAULT 1 COMMENT '1=enabled, 0=disabled',
  `enable_gst` TINYINT(1) DEFAULT 1 COMMENT '1=show GST columns on invoice, 0=hide',
  `tax_rate` DECIMAL(5,2) DEFAULT 18.00 COMMENT 'Default GST rate',
  `low_stock_threshold` INT DEFAULT 10,
  `theme_color` VARCHAR(20) DEFAULT '#4e73df',
  `invoice_title` VARCHAR(255) DEFAULT 'Tax Invoice' COMMENT 'Title shown on invoice',
  `invoice_subtitle` VARCHAR(255) DEFAULT NULL COMMENT 'Optional subtitle below title',
  `invoice_show_logo` TINYINT(1) DEFAULT 1 COMMENT '1=show logo on invoice',
  `invoice_show_payment_status` TINYINT(1) DEFAULT 1 COMMENT '1=show paid/unpaid badge',
  `invoice_footer_text` TEXT DEFAULT NULL COMMENT 'Custom footer text on invoice',
  `invoice_terms` TEXT DEFAULT NULL COMMENT 'Terms & conditions on invoice',
  `invoice_bank_details` TEXT DEFAULT NULL COMMENT 'Bank account details for payment',
  `invoice_signature_label` VARCHAR(255) DEFAULT 'Authorised Signatory' COMMENT 'Signature label text',
  `show_paid_due_on_invoice` TINYINT(1) DEFAULT 1 COMMENT '1=show paid/due summary on invoice',
  `show_unit_on_invoice` TINYINT(1) DEFAULT 0 COMMENT '1=show product unit next to quantity',
  `show_discount_on_invoice` TINYINT(1) DEFAULT 1 COMMENT '1=show discount column on invoice',
  `show_hsn_on_invoice` TINYINT(1) DEFAULT 1 COMMENT '1=show HSN/SAC column in GST invoices',
  `auto_round_off_rupee` TINYINT(1) DEFAULT 0 COMMENT '1=auto apply nearest ₹1 round-off on sale bills',
  `invoice_notes_label` VARCHAR(100) DEFAULT 'Notes' COMMENT 'Label for notes section',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. USERS / AUTHENTICATION
-- ============================================================
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `role` ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `theme_mode` ENUM('light','dark') DEFAULT 'light',
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_active` (`is_active`),
  INDEX `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB;

-- ============================================================
-- 3. CATEGORIES
-- ============================================================
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_categories_active` (`is_active`),
  INDEX `idx_categories_deleted` (`deleted_at`)
) ENGINE=InnoDB;

-- ============================================================
-- 4. BRANDS
-- ============================================================
CREATE TABLE `brands` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_brands_active` (`is_active`),
  INDEX `idx_brands_deleted` (`deleted_at`)
) ENGINE=InnoDB;

-- ============================================================
-- 5. UNITS
-- ============================================================
CREATE TABLE `units` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT 'e.g. Piece, Kg, Ltr',
  `short_name` VARCHAR(20) NOT NULL COMMENT 'e.g. pcs, kg, ltr',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_units_active` (`is_active`),
  INDEX `idx_units_deleted` (`deleted_at`)
) ENGINE=InnoDB;

-- ============================================================
-- 6. PRODUCTS
-- ============================================================
CREATE TABLE `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100) DEFAULT NULL UNIQUE,
  `barcode` VARCHAR(100) DEFAULT NULL,
  `hsn_code` VARCHAR(20) DEFAULT NULL,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `brand_id` INT UNSIGNED DEFAULT NULL,
  `unit_id` INT UNSIGNED DEFAULT NULL,
  `purchase_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `mrp` DECIMAL(12,2) DEFAULT NULL COMMENT 'Maximum Retail Price',
  `tax_rate` DECIMAL(5,2) DEFAULT NULL COMMENT 'Product-specific tax, NULL=use default',
  `opening_stock` DECIMAL(12,3) DEFAULT 0.000,
  `current_stock` DECIMAL(12,3) DEFAULT 0.000,
  `low_stock_alert` INT DEFAULT NULL COMMENT 'NULL = use global setting',
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE SET NULL,
  INDEX `idx_products_category` (`category_id`),
  INDEX `idx_products_brand` (`brand_id`),
  INDEX `idx_products_active` (`is_active`),
  INDEX `idx_products_stock` (`current_stock`),
  INDEX `idx_products_deleted` (`deleted_at`),
  INDEX `idx_products_sku` (`sku`)
) ENGINE=InnoDB;

-- ============================================================
-- 7. STOCK HISTORY LOG
-- ============================================================
CREATE TABLE `stock_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `type` ENUM('opening','purchase','purchase_return','purchase_edit','purchase_edit_reverse','purchase_cancel','sale','sale_return','sale_edit','sale_edit_reverse','sale_cancel','return','adjustment') NOT NULL,
  `reference_id` INT UNSIGNED DEFAULT NULL COMMENT 'ID of purchase/sale/etc.',
  `quantity` DECIMAL(12,3) NOT NULL COMMENT 'positive=in, negative=out',
  `stock_before` DECIMAL(12,3) NOT NULL,
  `stock_after` DECIMAL(12,3) NOT NULL,
  `note` VARCHAR(500) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_stock_product` (`product_id`),
  INDEX `idx_stock_type` (`type`),
  INDEX `idx_stock_date` (`created_at`)
) ENGINE=InnoDB;

-- ============================================================
-- 8. CUSTOMERS
-- ============================================================
CREATE TABLE `customers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `zip` VARCHAR(20) DEFAULT NULL,
  `tax_number` VARCHAR(100) DEFAULT NULL COMMENT 'Customer GST Number',
  `opening_balance` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'positive=due, negative=advance',
  `current_balance` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'positive=due, negative=advance',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_customers_active` (`is_active`),
  INDEX `idx_customers_balance` (`current_balance`),
  INDEX `idx_customers_deleted` (`deleted_at`)
) ENGINE=InnoDB;

-- ============================================================
-- 9. SUPPLIERS
-- ============================================================
CREATE TABLE `suppliers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `zip` VARCHAR(20) DEFAULT NULL,
  `tax_number` VARCHAR(100) DEFAULT NULL COMMENT 'Supplier GST Number',
  `opening_balance` DECIMAL(12,2) DEFAULT 0.00,
  `current_balance` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'positive=we owe, negative=advance',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_suppliers_active` (`is_active`),
  INDEX `idx_suppliers_balance` (`current_balance`),
  INDEX `idx_suppliers_deleted` (`deleted_at`)
) ENGINE=InnoDB;

-- ============================================================
-- 10. PURCHASES
-- ============================================================
CREATE TABLE `purchases` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `supplier_id` INT UNSIGNED NOT NULL,
  `purchase_date` DATE NOT NULL,
  `reference_number` VARCHAR(100) DEFAULT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(12,2) DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) DEFAULT 0.00,
  `shipping_cost` DECIMAL(12,2) DEFAULT 0.00,
  `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(12,2) DEFAULT 0.00,
  `due_amount` DECIMAL(12,2) DEFAULT 0.00,
  `payment_status` ENUM('unpaid','partial','paid') DEFAULT 'unpaid',
  `status` ENUM('received','pending','cancelled') DEFAULT 'received',
  `note` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_purchases_supplier` (`supplier_id`),
  INDEX `idx_purchases_date` (`purchase_date`),
  INDEX `idx_purchases_status` (`payment_status`),
  INDEX `idx_purchases_deleted` (`deleted_at`)
) ENGINE=InnoDB;

-- ============================================================
-- 11. PURCHASE ITEMS
-- ============================================================
CREATE TABLE `purchase_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `purchase_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(12,3) NOT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `discount` DECIMAL(12,2) DEFAULT 0.00,
  `tax_rate` DECIMAL(5,2) DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) DEFAULT 0.00,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `total` DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT,
  INDEX `idx_pitems_purchase` (`purchase_id`),
  INDEX `idx_pitems_product` (`product_id`)
) ENGINE=InnoDB;

-- ============================================================
-- 12. PURCHASE RETURNS
-- ============================================================
CREATE TABLE `purchase_returns` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `purchase_id` INT UNSIGNED NOT NULL,
  `return_date` DATE NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `note` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `purchase_return_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `return_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(12,3) NOT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `total` DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (`return_id`) REFERENCES `purchase_returns`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 13. SALES
-- ============================================================
CREATE TABLE `sales` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT UNSIGNED NOT NULL,
  `sale_date` DATE NOT NULL,
  `reference_number` VARCHAR(100) DEFAULT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(12,2) DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) DEFAULT 0.00,
  `gst_type` ENUM('auto','cgst_sgst','igst','none') DEFAULT 'auto',
  `shipping_cost` DECIMAL(12,2) DEFAULT 0.00,
  `freight_charge` DECIMAL(12,2) DEFAULT 0.00,
  `loading_charge` DECIMAL(12,2) DEFAULT 0.00,
  `round_off` DECIMAL(12,2) DEFAULT 0.00,
  `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(12,2) DEFAULT 0.00,
  `due_amount` DECIMAL(12,2) DEFAULT 0.00,
  `payment_status` ENUM('unpaid','partial','paid') DEFAULT 'unpaid',
  `status` ENUM('completed','pending','cancelled') DEFAULT 'completed',
  `note` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_sales_customer` (`customer_id`),
  INDEX `idx_sales_date` (`sale_date`),
  INDEX `idx_sales_status` (`payment_status`),
  INDEX `idx_sales_deleted` (`deleted_at`)
) ENGINE=InnoDB;

-- ============================================================
-- 14. SALE ITEMS
-- ============================================================
CREATE TABLE `sale_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `sale_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(12,3) NOT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `discount` DECIMAL(12,2) DEFAULT 0.00,
  `tax_rate` DECIMAL(5,2) DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) DEFAULT 0.00,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `total` DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT,
  INDEX `idx_sitems_sale` (`sale_id`),
  INDEX `idx_sitems_product` (`product_id`)
) ENGINE=InnoDB;

-- ============================================================
-- 15. SALE RETURNS
-- ============================================================
CREATE TABLE `sale_returns` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `sale_id` INT UNSIGNED NOT NULL,
  `return_date` DATE NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `note` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `sale_return_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `return_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(12,3) NOT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `total` DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (`return_id`) REFERENCES `sale_returns`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 16. PAYMENTS (Both Customer Receipts & Supplier Payments)
-- ============================================================
CREATE TABLE `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `payment_number` VARCHAR(50) NOT NULL UNIQUE,
  `type` ENUM('receipt','payment') NOT NULL COMMENT 'receipt=from customer, payment=to supplier',
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `supplier_id` INT UNSIGNED DEFAULT NULL,
  `sale_id` INT UNSIGNED DEFAULT NULL,
  `purchase_id` INT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_method` ENUM('cash','bank','cheque','online','other') DEFAULT 'cash',
  `payment_date` DATE NOT NULL,
  `reference_number` VARCHAR(100) DEFAULT NULL,
  `bank_name` VARCHAR(255) DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_payments_type` (`type`),
  INDEX `idx_payments_customer` (`customer_id`),
  INDEX `idx_payments_supplier` (`supplier_id`),
  INDEX `idx_payments_date` (`payment_date`),
  INDEX `idx_payments_deleted` (`deleted_at`)
) ENGINE=InnoDB;

-- ============================================================
-- 17. ACTIVITY LOG
-- ============================================================
CREATE TABLE `activity_log` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(255) NOT NULL,
  `module` VARCHAR(100) DEFAULT NULL,
  `reference_id` INT UNSIGNED DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_activity_user` (`user_id`),
  INDEX `idx_activity_module` (`module`),
  INDEX `idx_activity_date` (`created_at`)
) ENGINE=InnoDB;

-- ============================================================
-- INSERT DEFAULT DATA
-- ============================================================

-- Default company settings
INSERT INTO `company_settings` (`company_name`, `company_email`, `company_phone`, `company_address`, `company_city`, `company_state`, `company_country`)
VALUES ('My Business', 'info@mybusiness.com', '+91 9876543210', '123 Main Street', 'Mumbai', 'Maharashtra', 'India');

-- Default admin user (password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `is_active`)
VALUES ('admin', 'admin@mybusiness.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 1);

-- Default staff user (password: staff123)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `is_active`)
VALUES ('staff', 'staff@mybusiness.com', '$2y$10$Ep4yVGHQ.tR7LIbIbKUaYOB5MmGDkBCqvPNs1.TwtIxCDmC5ri8Ci', 'Staff User', 'staff', 1);

-- Default units
INSERT INTO `units` (`name`, `short_name`) VALUES
  ('Piece', 'pcs'),
  ('Kilogram', 'kg'),
  ('Gram', 'g'),
  ('Litre', 'ltr'),
  ('Millilitre', 'ml'),
  ('Meter', 'm'),
  ('Box', 'box'),
  ('Dozen', 'dz'),
  ('Pack', 'pack'),
  ('Set', 'set');

-- Default categories
INSERT INTO `categories` (`name`, `description`) VALUES
  ('Electronics', 'Electronic products and gadgets'),
  ('Clothing', 'Garments and apparel'),
  ('Grocery', 'Food items and groceries'),
  ('Hardware', 'Hardware tools and items'),
  ('Stationery', 'Office and school supplies'),
  ('Furniture', 'Home and office furniture');

-- Default brands
INSERT INTO `brands` (`name`) VALUES
  ('Generic'),
  ('Samsung'),
  ('Apple'),
  ('HP'),
  ('Dell'),
  ('Local');

-- Sample customers
INSERT INTO `customers` (`name`, `email`, `phone`, `address`, `city`, `state`, `tax_number`) VALUES
  ('Rajesh Kumar', 'rajesh@email.com', '9876543001', '45, MG Road', 'Mumbai', 'Maharashtra', '27AABCU9603R1ZM'),
  ('Priya Sharma', 'priya@email.com', '9876543002', '12, Park Street', 'Delhi', 'Delhi', NULL),
  ('Mohammed Ali', 'ali@email.com', '9876543003', '78, Brigade Road', 'Bangalore', 'Karnataka', '29AABCU9603R1ZK'),
  ('Sneha Patel', 'sneha@email.com', '9876543004', '90, Ring Road', 'Ahmedabad', 'Gujarat', NULL),
  ('Walk-in Customer', NULL, NULL, NULL, NULL, NULL, NULL);

-- Sample suppliers
INSERT INTO `suppliers` (`name`, `email`, `phone`, `address`, `city`, `state`, `tax_number`) VALUES
  ('ABC Distributors', 'abc@supplier.com', '9876543101', '23, Industrial Area', 'Mumbai', 'Maharashtra', '27AABCD1234R1ZP'),
  ('XYZ Traders', 'xyz@supplier.com', '9876543102', '56, MIDC Zone', 'Pune', 'Maharashtra', '27AABCE5678R1ZQ'),
  ('Global Imports', 'global@supplier.com', '9876543103', '89, Export Nagar', 'Delhi', 'Delhi', '07AABCF9101R1ZR'),
  ('Local Wholesale', 'local@supplier.com', '9876543104', '34, Market Road', 'Bangalore', 'Karnataka', NULL);

-- Sample products
INSERT INTO `products` (`name`, `sku`, `category_id`, `brand_id`, `unit_id`, `purchase_price`, `selling_price`, `opening_stock`, `current_stock`) VALUES
  ('Laptop HP Pavilion', 'LAP-001', 1, 4, 1, 45000.00, 52000.00, 15, 15),
  ('Samsung Galaxy S24', 'MOB-001', 1, 2, 1, 55000.00, 65000.00, 20, 20),
  ('Wireless Mouse', 'ACC-001', 1, 1, 1, 250.00, 450.00, 100, 100),
  ('USB-C Cable', 'ACC-002', 1, 1, 1, 80.00, 150.00, 200, 200),
  ('Office Chair', 'FUR-001', 6, 1, 1, 5500.00, 7500.00, 10, 10),
  ('A4 Paper Ream', 'STN-001', 5, 1, 7, 180.00, 250.00, 50, 50),
  ('Basmati Rice 5kg', 'GRC-001', 3, 6, 8, 350.00, 450.00, 30, 30),
  ('Cotton T-Shirt', 'CLT-001', 2, 6, 1, 200.00, 400.00, 75, 75),
  ('Steel Hammer', 'HRD-001', 4, 1, 1, 180.00, 300.00, 40, 40),
  ('Notebook 200pg', 'STN-002', 5, 1, 1, 40.00, 70.00, 150, 150);

-- Insert opening stock history
INSERT INTO `stock_history` (`product_id`, `type`, `quantity`, `stock_before`, `stock_after`, `note`, `created_by`)
SELECT id, 'opening', opening_stock, 0, opening_stock, 'Opening stock entry', 1 FROM `products`;
