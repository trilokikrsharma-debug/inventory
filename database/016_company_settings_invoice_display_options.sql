-- ============================================================
-- 016: Company settings invoice display option flags
-- Adds persistent switches for invoice rendering controls.
-- ============================================================

ALTER TABLE `company_settings`
ADD COLUMN `show_paid_due_on_invoice` TINYINT(1) NOT NULL DEFAULT 1 AFTER `invoice_signature_label`,
ADD COLUMN `show_unit_on_invoice` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show_paid_due_on_invoice`,
ADD COLUMN `show_discount_on_invoice` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_unit_on_invoice`;
