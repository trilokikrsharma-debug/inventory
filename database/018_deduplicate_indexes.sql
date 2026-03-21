-- ============================================================================
-- 018_deduplicate_indexes.sql
-- Remove redundant duplicate indexes accumulated across legacy migrations.
-- Safe to re-run with migration runner idempotent guards.
-- ============================================================================

-- Users
ALTER TABLE users DROP INDEX idx_users_company_username;
ALTER TABLE users DROP INDEX idx_users_tenant_username;

-- Sales / Purchases / Payments invoice-number uniqueness
ALTER TABLE sales DROP INDEX idx_sales_company_invoice;
ALTER TABLE sales DROP INDEX uq_sales_tenant_invoice;
ALTER TABLE purchases DROP INDEX idx_purchases_company_invoice;
ALTER TABLE purchases DROP INDEX uq_purchases_tenant_invoice;
ALTER TABLE payments DROP INDEX uq_payments_tenant_number;

-- Products SKU and tenant filters
ALTER TABLE products DROP INDEX idx_prod_company_sku;
ALTER TABLE products DROP INDEX idx_products_company_sku;
ALTER TABLE products DROP INDEX uq_products_tenant_sku;
ALTER TABLE products DROP INDEX idx_products_tenant_cat;
ALTER TABLE products DROP INDEX idx_products_tenant_active;
ALTER TABLE products DROP INDEX idx_prod_company_active;

-- Company settings / audit / activity
ALTER TABLE company_settings DROP INDEX idx_cs_company;
ALTER TABLE company_settings DROP INDEX idx_settings_company;
ALTER TABLE audit_trail DROP INDEX idx_audit_company_table;
ALTER TABLE activity_log DROP INDEX idx_actlog_company_date;

-- Tenant dashboard and list duplicates
ALTER TABLE customers DROP INDEX idx_customers_tenant_balance;
ALTER TABLE sales DROP INDEX idx_sales_tenant_date;
ALTER TABLE sales DROP INDEX idx_sales_tenant_customer;
ALTER TABLE purchases DROP INDEX idx_purchases_tenant_date;
ALTER TABLE purchases DROP INDEX idx_purchases_tenant_supplier;
ALTER TABLE payments DROP INDEX idx_payments_tenant_date;
ALTER TABLE payments DROP INDEX idx_pay_company_customer;
ALTER TABLE payments DROP INDEX idx_pay_company_supplier;

-- Item table duplicates
ALTER TABLE sale_items DROP INDEX idx_sitems_tenant_sale;
ALTER TABLE sale_items DROP INDEX idx_si_company_sale;
ALTER TABLE sale_items DROP INDEX idx_sitems_product;
ALTER TABLE purchase_items DROP INDEX idx_pitems_tenant_purchase;
ALTER TABLE purchase_items DROP INDEX idx_pi_company_purchase;
ALTER TABLE purchase_items DROP INDEX idx_pitems_product;
ALTER TABLE sale_return_items DROP INDEX idx_sri_company;
ALTER TABLE purchase_return_items DROP INDEX idx_pri_company;

-- Stock history duplicate set
ALTER TABLE stock_history DROP INDEX idx_stockh_tenant_product;
ALTER TABLE stock_history DROP INDEX idx_sh_company_product;
ALTER TABLE stock_history DROP INDEX idx_stock_tenant_product;

-- API token duplicate
ALTER TABLE api_tokens DROP INDEX idx_api_tokens_hash;

-- ============================================================================
-- End
-- ============================================================================
