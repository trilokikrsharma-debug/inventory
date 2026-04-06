-- ============================================================================
-- 027: Add missing purchase invoice title setting
--
-- PURPOSE:
--   - Align database schema with existing settings/invoice code
--   - Ensure old and new tenants have a sane default for purchase invoices
-- ============================================================================

ALTER TABLE `company_settings`
    ADD COLUMN IF NOT EXISTS `purchase_invoice_title` VARCHAR(100) DEFAULT 'Purchase Bill' AFTER `invoice_title`;

UPDATE `company_settings`
SET `purchase_invoice_title` = 'Purchase Bill'
WHERE `purchase_invoice_title` IS NULL OR TRIM(`purchase_invoice_title`) = '';
