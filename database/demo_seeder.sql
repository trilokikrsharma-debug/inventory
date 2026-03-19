-- ============================================================
-- DEMO COMPANY SEEDER
-- Seeds a complete demo company with sample data
-- Run AFTER the multi_tenant_migration.sql
-- ============================================================

-- NOTE:
-- Database selection should be done by the active connection (DB_NAME).

-- Ensure demo company exists
INSERT IGNORE INTO `companies` (`id`, `name`, `slug`, `plan`, `status`, `is_demo`, `max_users`, `max_products`)
VALUES (999, 'InvenBill Demo Store', 'demo-store', 'pro', 'active', 1, 99, 9999);

-- Create demo admin user (password: demo123)
INSERT IGNORE INTO `users` (`company_id`, `username`, `email`, `password`, `full_name`, `phone`, `role`, `role_id`, `is_active`)
VALUES (999, 'demo', 'demo@invenbill.com',
    '$2y$10$mFl6YkIEcSbMdcF5dRvLhu.e8qf0F1RPt9hN.Rz8p3YIpWvC9LXHS',
    'Demo Admin', '9999999999', 'admin', 1, 1);

-- Update company owner
UPDATE `companies` SET `owner_user_id` = (SELECT id FROM `users` WHERE company_id = 999 AND username = 'demo' LIMIT 1) WHERE id = 999;

-- Create demo company settings
INSERT IGNORE INTO `company_settings` (`company_id`, `company_name`, `company_email`, `company_phone`, `company_address`,
    `company_city`, `company_state`, `company_country`, `currency_symbol`, `currency_code`,
    `enable_gst`, `enable_tax`, `tax_rate`, `low_stock_threshold`,
    `invoice_prefix`, `purchase_prefix`, `payment_prefix`, `receipt_prefix`)
VALUES (999, 'InvenBill Demo Store', 'demo@invenbill.com', '9999999999',
    '123 Demo Street, Market Area', 'Mumbai', 'Maharashtra', 'India',
    '₹', 'INR', 1, 1, 18, 10,
    'DEMO-INV-', 'DEMO-PUR-', 'DEMO-PAY-', 'DEMO-REC-');

-- Seed Categories
INSERT IGNORE INTO `categories` (`company_id`, `name`) VALUES
(999, 'Electronics'),
(999, 'Groceries'),
(999, 'Clothing'),
(999, 'Stationery'),
(999, 'Home & Kitchen');

-- Seed Brands
INSERT IGNORE INTO `brands` (`company_id`, `name`) VALUES
(999, 'Samsung'),
(999, 'Apple'),
(999, 'Local Brand'),
(999, 'Generic'),
(999, 'Unbranded');

-- Seed Units
INSERT IGNORE INTO `units` (`company_id`, `name`, `short_name`) VALUES
(999, 'Pieces', 'pcs'),
(999, 'Kilograms', 'kg'),
(999, 'Liters', 'ltr'),
(999, 'Meters', 'mtr'),
(999, 'Boxes', 'box'),
(999, 'Dozen', 'dz');

-- Seed Walk-In Customer
INSERT IGNORE INTO `customers` (`company_id`, `name`, `phone`, `email`, `address`) VALUES
(999, 'Walk-In Customer', '', '', '');

-- Seed Sample Customers
INSERT IGNORE INTO `customers` (`company_id`, `name`, `phone`, `email`, `address`, `city`, `state`) VALUES
(999, 'Rajesh Kumar', '9876543210', 'rajesh@example.com', '45 Gandhi Road', 'Mumbai', 'Maharashtra'),
(999, 'Priya Sharma', '9876543211', 'priya@example.com', '12 MG Road', 'Delhi', 'Delhi'),
(999, 'Amit Patel', '9876543212', 'amit@example.com', '78 Station Road', 'Ahmedabad', 'Gujarat');

-- Seed Sample Suppliers
INSERT IGNORE INTO `suppliers` (`company_id`, `name`, `phone`, `email`, `address`, `city`, `state`) VALUES
(999, 'Tech Distributors Pvt Ltd', '9811111111', 'tech@supplier.com', 'Industrial Area', 'Mumbai', 'Maharashtra'),
(999, 'Metro Wholesale', '9822222222', 'metro@supplier.com', 'Wholesale Market', 'Delhi', 'Delhi'),
(999, 'Quality Imports', '9833333333', 'quality@supplier.com', 'Port Area', 'Chennai', 'Tamil Nadu');

-- ============================================================
-- NOTE: Sample products, sales, and purchases should be created 
-- through the application's normal flow after running this seeder,
-- as they involve complex stock/balance logic.
-- ============================================================
