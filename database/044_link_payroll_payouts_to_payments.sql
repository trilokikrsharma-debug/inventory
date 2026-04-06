-- HR phase 7: finance-linked payroll payout postings.

ALTER TABLE hr_payroll_items
    ADD COLUMN payroll_payment_id INT UNSIGNED NULL AFTER paid_at,
    ADD KEY idx_hr_payroll_payment_link (company_id, payroll_payment_id);

ALTER TABLE payments
    ADD COLUMN payroll_item_id INT UNSIGNED NULL AFTER purchase_id,
    ADD KEY idx_payments_payroll_item (company_id, payroll_item_id, deleted_at);
