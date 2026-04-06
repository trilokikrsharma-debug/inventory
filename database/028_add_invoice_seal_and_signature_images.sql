-- ============================================================================
-- 028: Add seal and signature images to company settings
-- ============================================================================

ALTER TABLE `company_settings`
    ADD COLUMN IF NOT EXISTS `invoice_signature_image` VARCHAR(255) DEFAULT NULL AFTER `purchase_invoice_title`,
    ADD COLUMN IF NOT EXISTS `invoice_seal_image` VARCHAR(255) DEFAULT NULL AFTER `invoice_signature_image`;
