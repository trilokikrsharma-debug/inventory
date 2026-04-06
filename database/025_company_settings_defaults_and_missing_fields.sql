-- ============================================================================
-- 025: Company settings safer defaults + missing invoice fields
-- Adds older-schema columns already supported in code and aligns defaults
-- for new tenants with safer starter behavior.
-- ============================================================================

ALTER TABLE `company_settings`
    ADD COLUMN IF NOT EXISTS `date_format` VARCHAR(20) DEFAULT 'd-m-Y' AFTER `currency_code`,
    ADD COLUMN IF NOT EXISTS `timezone` VARCHAR(50) DEFAULT 'Asia/Kolkata' AFTER `date_format`,
    ADD COLUMN IF NOT EXISTS `invoice_subtitle` VARCHAR(255) DEFAULT NULL AFTER `invoice_title`,
    ADD COLUMN IF NOT EXISTS `invoice_show_logo` TINYINT(1) DEFAULT 1 AFTER `invoice_subtitle`,
    ADD COLUMN IF NOT EXISTS `invoice_show_payment_status` TINYINT(1) DEFAULT 1 AFTER `invoice_show_logo`,
    ADD COLUMN IF NOT EXISTS `invoice_notes_label` VARCHAR(100) DEFAULT 'Notes' AFTER `auto_round_off_rupee`;

ALTER TABLE `company_settings`
    MODIFY COLUMN `enable_tax` TINYINT(1) DEFAULT 0,
    MODIFY COLUMN `enable_gst` TINYINT(1) DEFAULT 0,
    MODIFY COLUMN `tax_rate` DECIMAL(5,2) DEFAULT 0.00,
    MODIFY COLUMN `show_paid_due_on_invoice` TINYINT(1) DEFAULT 0,
    MODIFY COLUMN `show_unit_on_invoice` TINYINT(1) DEFAULT 1,
    MODIFY COLUMN `show_discount_on_invoice` TINYINT(1) DEFAULT 0,
    MODIFY COLUMN `show_hsn_on_invoice` TINYINT(1) DEFAULT 0;
