-- Warehouse transfer approval workflow.

ALTER TABLE stock_transfers
    ADD COLUMN status ENUM('pending','approved') NOT NULL DEFAULT 'pending' AFTER note,
    ADD COLUMN approved_by INT UNSIGNED NULL AFTER created_by,
    ADD COLUMN approved_at DATETIME NULL AFTER approved_by,
    ADD KEY idx_stock_transfers_status (company_id, status, transfer_date);

UPDATE stock_transfers
SET status = 'approved',
    approved_by = created_by,
    approved_at = created_at
WHERE status = 'pending';

