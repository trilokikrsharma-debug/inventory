-- ============================================================
-- InvenBill Pro — Composite Index Optimization
-- 
-- PURPOSE: Add multi-column indexes optimized for tenant-scoped
-- queries. Eliminates full-table scans at 100K+ records per tenant.
--
-- Indexes are normalized and applied idempotently by the migration runner.
-- ============================================================

-- ──────────────────────────────────
-- PRODUCTS: Most common listing & search queries
-- ──────────────────────────────────
-- Covers: WHERE company_id=? AND deleted_at IS NULL AND is_active=1
CREATE INDEX `idx_products_tenant_active`
    ON `products`(`company_id`, `deleted_at`, `is_active`);

-- Covers: WHERE company_id=? AND category_id=? AND deleted_at IS NULL
CREATE INDEX `idx_products_tenant_cat`
    ON `products`(`company_id`, `category_id`, `deleted_at`);

-- Covers: WHERE company_id=? AND (current_stock < low_stock_alert)
CREATE INDEX `idx_products_tenant_stock`
    ON `products`(`company_id`, `current_stock`, `low_stock_alert`);

-- ──────────────────────────────────
-- SALES: Listing, date filtering, payment status filtering
-- ──────────────────────────────────
-- Covers: WHERE company_id=? AND deleted_at IS NULL ORDER BY sale_date DESC
CREATE INDEX `idx_sales_tenant_date`
    ON `sales`(`company_id`, `deleted_at`, `sale_date`);

-- Covers: WHERE company_id=? AND deleted_at IS NULL AND payment_status=?
CREATE INDEX `idx_sales_tenant_status`
    ON `sales`(`company_id`, `deleted_at`, `payment_status`);

-- Covers: WHERE company_id=? AND customer_id=? AND deleted_at IS NULL
CREATE INDEX `idx_sales_tenant_customer`
    ON `sales`(`company_id`, `customer_id`, `deleted_at`);

-- ──────────────────────────────────
-- SALE ITEMS: JOIN optimization for sale detail queries
-- ──────────────────────────────────
-- Covers: WHERE company_id=? AND sale_id=?
CREATE INDEX `idx_sitems_tenant_sale`
    ON `sale_items`(`company_id`, `sale_id`);

-- Covers: JOIN products on sale_items — product summary reports
CREATE INDEX `idx_sitems_tenant_product`
    ON `sale_items`(`company_id`, `product_id`);

-- ──────────────────────────────────
-- PURCHASES: Listing & date filtering
-- ──────────────────────────────────
CREATE INDEX `idx_purchases_tenant_date`
    ON `purchases`(`company_id`, `deleted_at`, `purchase_date`);

CREATE INDEX `idx_purchases_tenant_status`
    ON `purchases`(`company_id`, `deleted_at`, `payment_status`);

CREATE INDEX `idx_purchases_tenant_supplier`
    ON `purchases`(`company_id`, `supplier_id`, `deleted_at`);

-- ──────────────────────────────────
-- PURCHASE ITEMS
-- ──────────────────────────────────
CREATE INDEX `idx_pitems_tenant_purchase`
    ON `purchase_items`(`company_id`, `purchase_id`);

-- ──────────────────────────────────
-- CUSTOMERS: Listing & balance queries
-- ──────────────────────────────────
CREATE INDEX `idx_customers_tenant_active`
    ON `customers`(`company_id`, `deleted_at`, `is_active`);

CREATE INDEX `idx_customers_tenant_balance`
    ON `customers`(`company_id`, `current_balance`);

-- ──────────────────────────────────
-- SUPPLIERS
-- ──────────────────────────────────
CREATE INDEX `idx_suppliers_tenant_active`
    ON `suppliers`(`company_id`, `deleted_at`, `is_active`);

-- ──────────────────────────────────
-- PAYMENTS: Listing & date filtering
-- ──────────────────────────────────
CREATE INDEX `idx_payments_tenant_date`
    ON `payments`(`company_id`, `deleted_at`, `payment_date`);

CREATE INDEX `idx_payments_tenant_customer`
    ON `payments`(`company_id`, `customer_id`, `deleted_at`);

CREATE INDEX `idx_payments_tenant_supplier`
    ON `payments`(`company_id`, `supplier_id`, `deleted_at`);

-- ──────────────────────────────────
-- QUOTATIONS
-- ──────────────────────────────────
CREATE INDEX `idx_quotations_tenant_date`
    ON `quotations`(`company_id`, `deleted_at`, `quotation_date`);

CREATE INDEX `idx_quotations_tenant_customer`
    ON `quotations`(`company_id`, `customer_id`, `deleted_at`);

-- ──────────────────────────────────
-- ACTIVITY LOG: Cleanup & tenant queries
-- ──────────────────────────────────
CREATE INDEX `idx_activity_tenant_date`
    ON `activity_log`(`company_id`, `created_at`);

-- ──────────────────────────────────
-- STOCK HISTORY: Product-specific history queries
-- ──────────────────────────────────
CREATE INDEX `idx_stock_tenant_product`
    ON `stock_history`(`company_id`, `product_id`, `created_at`);

-- ──────────────────────────────────
-- USERS: Tenant-scoped user queries
-- ──────────────────────────────────
CREATE INDEX `idx_users_tenant_active`
    ON `users`(`company_id`, `deleted_at`, `is_active`);

-- Covers: login query WHERE username=? AND company_id=?
CREATE INDEX `idx_users_tenant_username`
    ON `users`(`company_id`, `username`);

-- ──────────────────────────────────
-- SALE RETURNS / PURCHASE RETURNS
-- ──────────────────────────────────
CREATE INDEX `idx_sale_returns_tenant`
    ON `sale_returns`(`company_id`, `deleted_at`, `return_date`);

CREATE INDEX `idx_purchase_returns_tenant`
    ON `purchase_returns`(`company_id`, `deleted_at`, `return_date`);
