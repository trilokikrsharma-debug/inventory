-- ============================================================
-- 015_sales_tax_charge_breakup.sql
-- Adds GST breakup mode + separate freight/loading charges on sales.
-- ============================================================

ALTER TABLE `sales`
    ADD COLUMN IF NOT EXISTS `gst_type` ENUM('auto','cgst_sgst','igst','none') NOT NULL DEFAULT 'auto' AFTER `tax_amount`,
    ADD COLUMN IF NOT EXISTS `freight_charge` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `shipping_cost`,
    ADD COLUMN IF NOT EXISTS `loading_charge` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `freight_charge`;

-- Backfill old records: keep existing shipping amount as freight by default.
UPDATE `sales`
SET
    `freight_charge` = COALESCE(`shipping_cost`, 0),
    `loading_charge` = COALESCE(`loading_charge`, 0)
WHERE
    COALESCE(`shipping_cost`, 0) > 0
    AND COALESCE(`freight_charge`, 0) = 0
    AND COALESCE(`loading_charge`, 0) = 0;
