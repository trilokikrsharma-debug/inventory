-- Migration: Add Dispatch & E-Way Bill Hub fields to Sales table
-- Implements Indian GST requirement for transport documentation on invoices > 50K

ALTER TABLE `sales`
ADD COLUMN `dispatch_vehicle` VARCHAR(50) DEFAULT NULL AFTER `shipping_cost`,
ADD COLUMN `dispatch_transporter` VARCHAR(100) DEFAULT NULL AFTER `dispatch_vehicle`,
ADD COLUMN `dispatch_lr_no` VARCHAR(50) DEFAULT NULL AFTER `dispatch_transporter`;

ALTER TABLE `purchases`
ADD COLUMN `dispatch_vehicle` VARCHAR(50) DEFAULT NULL AFTER `shipping_cost`,
ADD COLUMN `dispatch_transporter` VARCHAR(100) DEFAULT NULL AFTER `dispatch_vehicle`,
ADD COLUMN `dispatch_lr_no` VARCHAR(50) DEFAULT NULL AFTER `dispatch_transporter`;
