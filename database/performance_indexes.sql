-- ============================================================================
-- InvenBill Pro — Performance Index Migration
-- 
-- Recommended indexes for smooth performance at 10k+ invoices per tenant.
-- These target the most common WHERE/JOIN patterns found in the codebase.
--
-- SAFE TO RUN: Uses IF NOT EXISTS pattern (MySQL 8.0+).
-- ============================================================================

-- ─── SALES ───────────────────────────────────────────────────────────────────
-- Covers: getAllWithCustomer, getDashboardTotals, getTotals, getMonthlyData
ALTER TABLE sales
    ADD INDEX IF NOT EXISTS idx_sales_company_date (company_id, sale_date, deleted_at),
    ADD INDEX IF NOT EXISTS idx_sales_company_customer (company_id, customer_id, deleted_at),
    ADD INDEX IF NOT EXISTS idx_sales_company_status (company_id, payment_status, deleted_at);

-- ─── SALE ITEMS ──────────────────────────────────────────────────────────────
-- Covers: getWithDetails items join, getProfitData, getTopProducts
ALTER TABLE sale_items
    ADD INDEX IF NOT EXISTS idx_sale_items_company_sale (company_id, sale_id),
    ADD INDEX IF NOT EXISTS idx_sale_items_product (product_id);

-- ─── PURCHASES ───────────────────────────────────────────────────────────────
ALTER TABLE purchases
    ADD INDEX IF NOT EXISTS idx_purchases_company_date (company_id, purchase_date, deleted_at),
    ADD INDEX IF NOT EXISTS idx_purchases_company_supplier (company_id, supplier_id, deleted_at),
    ADD INDEX IF NOT EXISTS idx_purchases_company_status (company_id, payment_status, deleted_at);

-- ─── PURCHASE ITEMS ──────────────────────────────────────────────────────────
ALTER TABLE purchase_items
    ADD INDEX IF NOT EXISTS idx_purchase_items_company_purchase (company_id, purchase_id),
    ADD INDEX IF NOT EXISTS idx_purchase_items_product (product_id);

-- ─── PAYMENTS ────────────────────────────────────────────────────────────────
-- Covers: getAllPaginated, createPayment auto-apply, recalculate methods
ALTER TABLE payments
    ADD INDEX IF NOT EXISTS idx_payments_company_type (company_id, type, deleted_at),
    ADD INDEX IF NOT EXISTS idx_payments_company_customer (company_id, customer_id, type, deleted_at),
    ADD INDEX IF NOT EXISTS idx_payments_company_supplier (company_id, supplier_id, type, deleted_at),
    ADD INDEX IF NOT EXISTS idx_payments_company_date (company_id, payment_date, deleted_at);

-- ─── PRODUCTS ────────────────────────────────────────────────────────────────
-- Covers: search, getLowStock, getAllWithRelations
ALTER TABLE products
    ADD INDEX IF NOT EXISTS idx_products_company_active (company_id, is_active, deleted_at),
    ADD INDEX IF NOT EXISTS idx_products_company_stock (company_id, current_stock, is_active, deleted_at),
    ADD INDEX IF NOT EXISTS idx_products_company_category (company_id, category_id, deleted_at),
    ADD INDEX IF NOT EXISTS idx_products_company_sku (company_id, sku);

-- ─── CUSTOMERS ───────────────────────────────────────────────────────────────
ALTER TABLE customers
    ADD INDEX IF NOT EXISTS idx_customers_company_balance (company_id, current_balance, deleted_at),
    ADD INDEX IF NOT EXISTS idx_customers_company_name (company_id, name, deleted_at);

-- ─── SUPPLIERS ───────────────────────────────────────────────────────────────
ALTER TABLE suppliers
    ADD INDEX IF NOT EXISTS idx_suppliers_company_balance (company_id, current_balance, deleted_at),
    ADD INDEX IF NOT EXISTS idx_suppliers_company_name (company_id, name, deleted_at);

-- ─── SALE RETURNS ────────────────────────────────────────────────────────────
ALTER TABLE sale_returns
    ADD INDEX IF NOT EXISTS idx_sale_returns_company_date (company_id, return_date, deleted_at),
    ADD INDEX IF NOT EXISTS idx_sale_returns_sale (sale_id, deleted_at);

ALTER TABLE sale_return_items
    ADD INDEX IF NOT EXISTS idx_sale_return_items_company_return (company_id, return_id);

-- ─── QUOTATIONS ──────────────────────────────────────────────────────────────
ALTER TABLE quotations
    ADD INDEX IF NOT EXISTS idx_quotations_company_date (company_id, quotation_date, deleted_at),
    ADD INDEX IF NOT EXISTS idx_quotations_company_status (company_id, status, deleted_at);

ALTER TABLE quotation_items
    ADD INDEX IF NOT EXISTS idx_quotation_items_company_quote (company_id, quotation_id);

-- ─── STOCK HISTORY ───────────────────────────────────────────────────────────
ALTER TABLE stock_history
    ADD INDEX IF NOT EXISTS idx_stock_history_company_product (company_id, product_id, created_at);

-- ─── USERS ───────────────────────────────────────────────────────────────────
ALTER TABLE users
    ADD INDEX IF NOT EXISTS idx_users_company_active (company_id, is_active, deleted_at),
    ADD INDEX IF NOT EXISTS idx_users_email (email, deleted_at),
    ADD INDEX IF NOT EXISTS idx_users_username_company (company_id, username, deleted_at);

-- ─── COMPANY SETTINGS ────────────────────────────────────────────────────────
ALTER TABLE company_settings
    ADD UNIQUE INDEX IF NOT EXISTS idx_company_settings_company (company_id);

-- ----------------------------------------------------------------------------
-- PHASE 3 SCALING ADDITIONS
-- ----------------------------------------------------------------------------
-- Search-heavy invoice/payment lookups
ALTER TABLE sales
    ADD INDEX IF NOT EXISTS idx_sales_company_invoice_deleted (company_id, invoice_number, deleted_at),
    ADD INDEX IF NOT EXISTS idx_sales_company_deleted_date (company_id, deleted_at, sale_date);

ALTER TABLE purchases
    ADD INDEX IF NOT EXISTS idx_purchases_company_invoice_deleted (company_id, invoice_number, deleted_at),
    ADD INDEX IF NOT EXISTS idx_purchases_company_deleted_date (company_id, deleted_at, purchase_date);

ALTER TABLE payments
    ADD INDEX IF NOT EXISTS idx_payments_company_number_deleted (company_id, payment_number, deleted_at),
    ADD INDEX IF NOT EXISTS idx_payments_company_type_date_deleted (company_id, type, payment_date, deleted_at);

-- Contact and list search accelerators
ALTER TABLE customers
    ADD INDEX IF NOT EXISTS idx_customers_company_phone_deleted (company_id, phone, deleted_at),
    ADD INDEX IF NOT EXISTS idx_customers_company_email_deleted (company_id, email, deleted_at);

ALTER TABLE suppliers
    ADD INDEX IF NOT EXISTS idx_suppliers_company_phone_deleted (company_id, phone, deleted_at),
    ADD INDEX IF NOT EXISTS idx_suppliers_company_email_deleted (company_id, email, deleted_at);

ALTER TABLE products
    ADD INDEX IF NOT EXISTS idx_products_company_name_deleted (company_id, name, deleted_at),
    ADD INDEX IF NOT EXISTS idx_products_company_category_active_deleted (company_id, category_id, is_active, deleted_at);

-- Queue throughput for async jobs (reports/backups/emails)
ALTER TABLE jobs
    ADD INDEX IF NOT EXISTS idx_jobs_claim_window (status, queue, scheduled_at, priority, id),
    ADD INDEX IF NOT EXISTS idx_jobs_company_queue_status (company_id, queue, status, created_at);

-- ============================================================================
-- NOTES:
-- 1. Run during low-traffic window on production
-- 2. For MySQL 5.7, remove "IF NOT EXISTS" and use conditional logic
-- 3. Monitor slow query log after deployment to identify remaining gaps
-- 4. Expected improvement: 10-50x faster on tables with 10k+ rows per tenant
-- ============================================================================
