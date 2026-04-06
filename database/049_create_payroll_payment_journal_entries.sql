-- Finance traceability for payroll payouts.

CREATE TABLE IF NOT EXISTS payroll_payment_journal_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    payment_id INT UNSIGNED NOT NULL,
    payroll_item_id INT UNSIGNED NOT NULL,
    entry_side ENUM('debit','credit') NOT NULL,
    account_code VARCHAR(50) NOT NULL,
    account_name VARCHAR(120) NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    memo VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_payroll_journal_payment (company_id, payment_id),
    KEY idx_payroll_journal_item (company_id, payroll_item_id),
    KEY idx_payroll_journal_account (company_id, account_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

