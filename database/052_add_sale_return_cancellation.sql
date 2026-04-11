ALTER TABLE `sale_returns`
  ADD COLUMN `status` ENUM('posted','cancelled') NOT NULL DEFAULT 'posted' AFTER `total_amount`,
  ADD COLUMN `cancel_reason` VARCHAR(500) DEFAULT NULL AFTER `note`,
  ADD COLUMN `cancelled_at` DATETIME DEFAULT NULL AFTER `created_at`,
  ADD COLUMN `cancelled_by` INT UNSIGNED DEFAULT NULL AFTER `cancelled_at`;

ALTER TABLE `sale_returns`
  ADD INDEX `idx_sale_returns_status` (`company_id`, `status`, `deleted_at`, `return_date`);

ALTER TABLE `sale_returns`
  ADD CONSTRAINT `fk_sale_returns_cancelled_by`
  FOREIGN KEY (`cancelled_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

INSERT IGNORE INTO `permissions` (`name`, `display_name`, `module`)
VALUES ('returns.cancel', 'Cancel Sale Returns', 'returns');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions` WHERE `name` = 'returns.cancel';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` WHERE `name` = 'returns.cancel';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` WHERE `name` = 'returns.cancel';
