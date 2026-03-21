-- ============================================================
-- InvenBill Pro — Two-Factor Authentication
-- 
-- PURPOSE: Add 2FA fields to users table for TOTP-based
-- authentication compatible with Google Authenticator.
-- ============================================================

ALTER TABLE `users`
    ADD COLUMN `twofa_secret` VARCHAR(64) NULL DEFAULT NULL
        COMMENT 'TOTP secret key (base32 encoded)' AFTER `theme_mode`,
    ADD COLUMN `twofa_enabled` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1=2FA enforced on login' AFTER `twofa_secret`,
    ADD COLUMN `twofa_recovery_codes` JSON NULL DEFAULT NULL
        COMMENT 'Hashed backup recovery codes' AFTER `twofa_enabled`;

-- Index for quick lookup during login
CREATE INDEX `idx_users_twofa` ON `users`(`twofa_enabled`);
