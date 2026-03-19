-- =====================================================================
-- InvenBill Pro — Enterprise Database Hardening Migration
-- Run this AFTER the base schema and all prior migrations.
-- Safe: uses IF NOT EXISTS / conditional checks where possible.
-- =====================================================================

-- ─────────────────────────────────────────────────────────────────
-- 1. COMPOSITE INDEXES for Multi-Tenant Query Performance
-- ─────────────────────────────────────────────────────────────────
-- These indexes target the most frequent query patterns (tenant + filter + soft-delete).
-- Expected improvement: 30–50% faster reads on pages with tenant-scoped listings.

-- Sales: dashboard/report queries by company + date
CREATE INDEX IF NOT EXISTS idx_sales_company_date 
  ON sales (company_id, sale_date, deleted_at);

-- Sales: payment status filtering
CREATE INDEX IF NOT EXISTS idx_sales_company_status 
  ON sales (company_id, payment_status, deleted_at);

-- Sale Items: fetching items for a specific sale
CREATE INDEX IF NOT EXISTS idx_si_company_sale 
  ON sale_items (company_id, sale_id);

-- Purchases: report queries by company + date
CREATE INDEX IF NOT EXISTS idx_purchases_company_date 
  ON purchases (company_id, purchase_date, deleted_at);

-- Purchase Items: fetching items for a specific purchase
CREATE INDEX IF NOT EXISTS idx_pi_company_purchase 
  ON purchase_items (company_id, purchase_id);

-- Products: active product listings (most common query)
CREATE INDEX IF NOT EXISTS idx_prod_company_active 
  ON products (company_id, is_active, deleted_at);

-- Products: search by SKU/barcode (autocomplete)
CREATE INDEX IF NOT EXISTS idx_prod_company_sku 
  ON products (company_id, sku);

-- Stock History: product stock trail lookups
CREATE INDEX IF NOT EXISTS idx_sh_company_product 
  ON stock_history (company_id, product_id, created_at);

-- Payments: reconciliation queries
CREATE INDEX IF NOT EXISTS idx_pay_company_customer 
  ON payments (company_id, customer_id, type, deleted_at);

-- Payments: supplier payment lookups
CREATE INDEX IF NOT EXISTS idx_pay_company_supplier 
  ON payments (company_id, supplier_id, type, deleted_at);

-- Customers: company-scoped listing
CREATE INDEX IF NOT EXISTS idx_cust_company_active 
  ON customers (company_id, deleted_at);

-- Suppliers: company-scoped listing
CREATE INDEX IF NOT EXISTS idx_supp_company_active 
  ON suppliers (company_id, deleted_at);

-- Activity Log: per-company audit trail
CREATE INDEX IF NOT EXISTS idx_actlog_company_date 
  ON activity_log (company_id, created_at);

-- Users: login lookup by email (global unique)
CREATE INDEX IF NOT EXISTS idx_users_email 
  ON users (email, deleted_at);


-- ─────────────────────────────────────────────────────────────────
-- 2. UNIQUE CONSTRAINTS for Data Integrity
-- ─────────────────────────────────────────────────────────────────

-- Unique username per company (allows same username across companies)
-- NOTE: Only works if there are no existing duplicates. 
-- Run the following check first: 
--   SELECT company_id, username, COUNT(*) c FROM users GROUP BY company_id, username HAVING c > 1;
ALTER TABLE users 
  ADD UNIQUE INDEX IF NOT EXISTS idx_users_company_username (company_id, username);

-- Unique invoice number per company
ALTER TABLE sales 
  ADD UNIQUE INDEX IF NOT EXISTS idx_sales_company_invoice (company_id, invoice_number);

-- Unique purchase invoice number per company
ALTER TABLE purchases 
  ADD UNIQUE INDEX IF NOT EXISTS idx_purchases_company_invoice (company_id, invoice_number);

-- One settings row per company
ALTER TABLE company_settings 
  ADD UNIQUE INDEX IF NOT EXISTS idx_settings_company (company_id);


-- ─────────────────────────────────────────────────────────────────
-- 3. FOREIGN KEY CONSTRAINTS — Tenant Binding
-- ─────────────────────────────────────────────────────────────────
-- These prevent orphaned records and enforce referential integrity.
-- Using ON DELETE RESTRICT to prevent accidental cascade deletion.

-- Users → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_company');
SET @sql = IF(@fk_exists = 0, 
  'ALTER TABLE users ADD CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Products → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND CONSTRAINT_NAME = 'fk_products_company');
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE products ADD CONSTRAINT fk_products_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Categories → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND CONSTRAINT_NAME = 'fk_categories_company');
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE categories ADD CONSTRAINT fk_categories_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Sales → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'sales' AND CONSTRAINT_NAME = 'fk_sales_company');
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE sales ADD CONSTRAINT fk_sales_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Purchases → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND CONSTRAINT_NAME = 'fk_purchases_company');
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE purchases ADD CONSTRAINT fk_purchases_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Customers → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND CONSTRAINT_NAME = 'fk_customers_company');
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE customers ADD CONSTRAINT fk_customers_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Suppliers → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND CONSTRAINT_NAME = 'fk_suppliers_company');
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE suppliers ADD CONSTRAINT fk_suppliers_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Payments → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND CONSTRAINT_NAME = 'fk_payments_company');
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE payments ADD CONSTRAINT fk_payments_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Brands → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'brands' AND CONSTRAINT_NAME = 'fk_brands_company');
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE brands ADD CONSTRAINT fk_brands_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Units → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'units' AND CONSTRAINT_NAME = 'fk_units_company');
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE units ADD CONSTRAINT fk_units_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Stock History → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_history' AND CONSTRAINT_NAME = 'fk_stockhist_company');
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE stock_history ADD CONSTRAINT fk_stockhist_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Company Settings → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'company_settings' AND CONSTRAINT_NAME = 'fk_settings_company');
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE company_settings ADD CONSTRAINT fk_settings_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Activity Log → Companies
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_log' AND CONSTRAINT_NAME = 'fk_actlog_company');
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE activity_log ADD CONSTRAINT fk_actlog_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ─────────────────────────────────────────────────────────────────
-- 4. VERIFICATION QUERY — Run after migration to confirm
-- ─────────────────────────────────────────────────────────────────
SELECT 'Indexes added' AS status, COUNT(*) AS count 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() AND INDEX_NAME LIKE 'idx_%';

SELECT 'Foreign keys added' AS status, COUNT(*) AS count 
FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME LIKE 'fk_%';
