-- ============================================================
-- InvenBill Pro — Invoice Number Tenant Isolation
-- 
-- PURPOSE: Change invoice/payment number uniqueness from global
-- to per-tenant. Different companies must be able to use the
-- same invoice numbers (INV-001, etc.) independently.
-- ============================================================

-- ──────────────────────────────────
-- 1. SALES: invoice_number unique per company
-- ──────────────────────────────────
-- Drop global unique constraint
ALTER TABLE `sales` DROP INDEX IF EXISTS `invoice_number`;

-- Add tenant-scoped unique constraint
ALTER TABLE `sales`
    ADD UNIQUE INDEX `uq_sales_tenant_invoice` (`company_id`, `invoice_number`);

-- ──────────────────────────────────
-- 2. PURCHASES: invoice_number unique per company
-- ──────────────────────────────────
ALTER TABLE `purchases` DROP INDEX IF EXISTS `invoice_number`;

ALTER TABLE `purchases`
    ADD UNIQUE INDEX `uq_purchases_tenant_invoice` (`company_id`, `invoice_number`);

-- ──────────────────────────────────
-- 3. PAYMENTS: payment_number unique per company
-- ──────────────────────────────────
ALTER TABLE `payments` DROP INDEX IF EXISTS `payment_number`;

ALTER TABLE `payments`
    ADD UNIQUE INDEX `uq_payments_tenant_number` (`company_id`, `payment_number`);

-- ──────────────────────────────────
-- 4. QUOTATIONS: quotation_number unique per company (if exists)
-- ──────────────────────────────────
ALTER TABLE `quotations` DROP INDEX IF EXISTS `quotation_number`;

ALTER TABLE `quotations`
    ADD UNIQUE INDEX `uq_quotations_tenant_number` (`company_id`, `quotation_number`);

-- ──────────────────────────────────
-- 5. PRODUCTS: SKU unique per company (different tenants can reuse SKUs)
-- ──────────────────────────────────
ALTER TABLE `products` DROP INDEX IF EXISTS `sku`;

ALTER TABLE `products`
    ADD UNIQUE INDEX `uq_products_tenant_sku` (`company_id`, `sku`);
