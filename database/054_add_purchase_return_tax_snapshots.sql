-- Add tax snapshots to purchase return items so GST reports can net input-tax reversals.
ALTER TABLE `purchase_return_items`
    ADD COLUMN IF NOT EXISTS `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `unit_price`,
    ADD COLUMN IF NOT EXISTS `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`,
    ADD COLUMN IF NOT EXISTS `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `tax_rate`;

UPDATE `purchase_return_items` pri
JOIN `purchase_returns` pr ON pr.id = pri.return_id
JOIN `purchase_items` pi ON pi.purchase_id = pr.purchase_id AND pi.product_id = pri.product_id
SET
    pri.subtotal = ROUND(COALESCE(pi.subtotal, 0) * (pri.quantity / NULLIF(pi.quantity, 0)), 2),
    pri.tax_rate = COALESCE(pi.tax_rate, 0),
    pri.tax_amount = ROUND(COALESCE(pi.tax_amount, 0) * (pri.quantity / NULLIF(pi.quantity, 0)), 2),
    pri.total = ROUND(COALESCE(pi.total, pri.total) * (pri.quantity / NULLIF(pi.quantity, 0)), 2)
WHERE pri.subtotal = 0
  AND pi.quantity > 0;
