-- ============================================================================
-- 029: Add show/hide flags for invoice seal and signature
-- ============================================================================

ALTER TABLE `company_settings`
    ADD COLUMN IF NOT EXISTS `invoice_show_signature` TINYINT(1) DEFAULT 1 AFTER `invoice_seal_image`,
    ADD COLUMN IF NOT EXISTS `invoice_show_seal` TINYINT(1) DEFAULT 1 AFTER `invoice_show_signature`;
