-- ============================================================
-- 017: GST HSN + auto round-off settings
-- Adds HSN/SAC support for products and invoice-level behavior flags.
-- ============================================================

ALTER TABLE `products`
ADD COLUMN IF NOT EXISTS `hsn_code` VARCHAR(20) DEFAULT NULL AFTER `barcode`;

ALTER TABLE `company_settings`
ADD COLUMN IF NOT EXISTS `show_hsn_on_invoice` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_discount_on_invoice`,
ADD COLUMN IF NOT EXISTS `auto_round_off_rupee` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show_hsn_on_invoice`;
