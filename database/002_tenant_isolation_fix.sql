-- ============================================================
-- InvenBill Pro — Tenant Isolation Fix Migration
-- 
-- PURPOSE: Add company_id to tables that are missing it,
-- ensuring complete tenant data isolation.
--
-- SAFETY: Uses IF NOT EXISTS / IF EXISTS guards.
-- RUN AFTER: multi_tenant_migration.sql
-- ============================================================

-- ──────────────────────────────────
-- 1. sale_returns
-- ──────────────────────────────────
ALTER TABLE `sale_returns`
    ADD COLUMN IF NOT EXISTS `company_id` INT UNSIGNED NULL AFTER `id`;

UPDATE `sale_returns` sr
    JOIN `sales` s ON sr.sale_id = s.id
    SET sr.company_id = s.company_id
    WHERE sr.company_id IS NULL;

-- Only enforce NOT NULL if data is populated
-- ALTER TABLE `sale_returns` MODIFY `company_id` INT UNSIGNED NOT NULL;

CREATE INDEX IF NOT EXISTS `idx_sr_company` ON `sale_returns`(`company_id`, `deleted_at`);

-- ──────────────────────────────────
-- 2. sale_return_items
-- ──────────────────────────────────
ALTER TABLE `sale_return_items`
    ADD COLUMN IF NOT EXISTS `company_id` INT UNSIGNED NULL AFTER `id`;

UPDATE `sale_return_items` sri
    JOIN `sale_returns` sr ON sri.return_id = sr.id
    SET sri.company_id = sr.company_id
    WHERE sri.company_id IS NULL;

CREATE INDEX IF NOT EXISTS `idx_sri_company` ON `sale_return_items`(`company_id`);

-- ──────────────────────────────────
-- 3. quotation_items
-- ──────────────────────────────────
ALTER TABLE `quotation_items`
    ADD COLUMN IF NOT EXISTS `company_id` INT UNSIGNED NULL AFTER `id`;

UPDATE `quotation_items` qi
    JOIN `quotations` q ON qi.quotation_id = q.id
    SET qi.company_id = q.company_id
    WHERE qi.company_id IS NULL;

CREATE INDEX IF NOT EXISTS `idx_qi_company` ON `quotation_items`(`company_id`);

-- ──────────────────────────────────
-- 4. purchase_returns
-- ──────────────────────────────────
ALTER TABLE `purchase_returns`
    ADD COLUMN IF NOT EXISTS `company_id` INT UNSIGNED NULL AFTER `id`;

UPDATE `purchase_returns` pr
    JOIN `purchases` p ON pr.purchase_id = p.id
    SET pr.company_id = p.company_id
    WHERE pr.company_id IS NULL;

CREATE INDEX IF NOT EXISTS `idx_pr_company` ON `purchase_returns`(`company_id`, `deleted_at`);

-- ──────────────────────────────────
-- 5. purchase_return_items
-- ──────────────────────────────────
ALTER TABLE `purchase_return_items`
    ADD COLUMN IF NOT EXISTS `company_id` INT UNSIGNED NULL AFTER `id`;

UPDATE `purchase_return_items` pri
    JOIN `purchase_returns` pr ON pri.return_id = pr.id
    SET pri.company_id = pr.company_id
    WHERE pri.company_id IS NULL;

CREATE INDEX IF NOT EXISTS `idx_pri_company` ON `purchase_return_items`(`company_id`);

-- ============================================================
-- VERIFY: Check that all rows have company_id populated
-- ============================================================
-- SELECT 'sale_returns' as tbl, COUNT(*) as missing FROM sale_returns WHERE company_id IS NULL
-- UNION ALL
-- SELECT 'sale_return_items', COUNT(*) FROM sale_return_items WHERE company_id IS NULL
-- UNION ALL
-- SELECT 'quotation_items', COUNT(*) FROM quotation_items WHERE company_id IS NULL
-- UNION ALL
-- SELECT 'purchase_returns', COUNT(*) FROM purchase_returns WHERE company_id IS NULL
-- UNION ALL
-- SELECT 'purchase_return_items', COUNT(*) FROM purchase_return_items WHERE company_id IS NULL;
