-- Warehouse transfer rejection and cancellation workflow.

ALTER TABLE stock_transfers
    MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    ADD COLUMN rejected_by INT UNSIGNED NULL AFTER approved_at,
    ADD COLUMN rejected_at DATETIME NULL AFTER rejected_by,
    ADD COLUMN rejection_reason TEXT NULL AFTER rejected_at;

