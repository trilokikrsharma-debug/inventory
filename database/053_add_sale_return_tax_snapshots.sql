-- Add tax snapshots to sale return items so GST reports can net posted returns.
ALTER TABLE `sale_return_items`
    ADD COLUMN IF NOT EXISTS `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `unit_price`,
    ADD COLUMN IF NOT EXISTS `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`,
    ADD COLUMN IF NOT EXISTS `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `tax_rate`;

UPDATE `sale_return_items` sri
JOIN `sale_returns` sr ON sr.id = sri.return_id
JOIN `sale_items` si ON si.sale_id = sr.sale_id AND si.product_id = sri.product_id
SET
    sri.subtotal = ROUND(COALESCE(si.subtotal, 0) * (sri.quantity / NULLIF(si.quantity, 0)), 2),
    sri.tax_rate = COALESCE(si.tax_rate, 0),
    sri.tax_amount = ROUND(COALESCE(si.tax_amount, 0) * (sri.quantity / NULLIF(si.quantity, 0)), 2),
    sri.total = ROUND(COALESCE(si.total, sri.total) * (sri.quantity / NULLIF(si.quantity, 0)), 2)
WHERE sri.subtotal = 0
  AND si.quantity > 0;
