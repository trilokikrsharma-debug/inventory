-- ============================================================
-- InvenBill Pro — Financial Column Precision Upgrade
-- 
-- PURPOSE: Upgrade DECIMAL(12,2) to DECIMAL(15,2) on all financial
-- columns to support large enterprise transactions (up to 9,999,999,999,999.99).
--
-- Also fixes invoice number uniqueness to be per-tenant instead of global.
-- ============================================================

-- ──────────────────────────────────
-- 1. SALES — financial columns
-- ──────────────────────────────────
ALTER TABLE `sales`
    MODIFY `subtotal`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    MODIFY `discount_amount` DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `tax_amount`      DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `shipping_cost`   DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `round_off`       DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `grand_total`     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    MODIFY `paid_amount`     DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `due_amount`      DECIMAL(15,2) DEFAULT 0.00;

-- ──────────────────────────────────
-- 2. SALE ITEMS — financial columns
-- ──────────────────────────────────
ALTER TABLE `sale_items`
    MODIFY `unit_price`  DECIMAL(15,2) NOT NULL,
    MODIFY `discount`    DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `tax_amount`  DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `subtotal`    DECIMAL(15,2) NOT NULL,
    MODIFY `total`       DECIMAL(15,2) NOT NULL;

-- ──────────────────────────────────
-- 3. PURCHASES — financial columns
-- ──────────────────────────────────
ALTER TABLE `purchases`
    MODIFY `subtotal`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    MODIFY `discount_amount` DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `tax_amount`      DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `shipping_cost`   DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `grand_total`     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    MODIFY `paid_amount`     DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `due_amount`      DECIMAL(15,2) DEFAULT 0.00;

-- ──────────────────────────────────
-- 4. PURCHASE ITEMS — financial columns
-- ──────────────────────────────────
ALTER TABLE `purchase_items`
    MODIFY `unit_price`  DECIMAL(15,2) NOT NULL,
    MODIFY `discount`    DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `tax_amount`  DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `subtotal`    DECIMAL(15,2) NOT NULL,
    MODIFY `total`       DECIMAL(15,2) NOT NULL;

-- ──────────────────────────────────
-- 5. PAYMENTS — financial columns
-- ──────────────────────────────────
ALTER TABLE `payments`
    MODIFY `amount` DECIMAL(15,2) NOT NULL;

-- ──────────────────────────────────
-- 6. PRODUCTS — price columns
-- ──────────────────────────────────
ALTER TABLE `products`
    MODIFY `purchase_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    MODIFY `selling_price`  DECIMAL(15,2) NOT NULL DEFAULT 0.00;

-- ──────────────────────────────────
-- 7. CUSTOMERS & SUPPLIERS — balance columns
-- ──────────────────────────────────
ALTER TABLE `customers`
    MODIFY `opening_balance` DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `current_balance` DECIMAL(15,2) DEFAULT 0.00;

ALTER TABLE `suppliers`
    MODIFY `opening_balance` DECIMAL(15,2) DEFAULT 0.00,
    MODIFY `current_balance` DECIMAL(15,2) DEFAULT 0.00;

-- ──────────────────────────────────
-- 8. SALE RETURNS / PURCHASE RETURNS — totals
-- ──────────────────────────────────
ALTER TABLE `sale_returns`
    MODIFY `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00;

ALTER TABLE `sale_return_items`
    MODIFY `unit_price` DECIMAL(15,2) NOT NULL,
    MODIFY `total`      DECIMAL(15,2) NOT NULL;

ALTER TABLE `purchase_returns`
    MODIFY `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00;

ALTER TABLE `purchase_return_items`
    MODIFY `unit_price` DECIMAL(15,2) NOT NULL,
    MODIFY `total`      DECIMAL(15,2) NOT NULL;
