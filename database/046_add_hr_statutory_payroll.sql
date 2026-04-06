-- HR phase 9: statutory payroll policy and deduction storage.

CREATE TABLE IF NOT EXISTS hr_payroll_policies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    enable_pf TINYINT(1) NOT NULL DEFAULT 0,
    pf_rate DECIMAL(8,2) NOT NULL DEFAULT 12.00,
    pf_salary_cap DECIMAL(15,2) NULL DEFAULT 15000.00,
    enable_esi TINYINT(1) NOT NULL DEFAULT 0,
    esi_rate DECIMAL(8,2) NOT NULL DEFAULT 0.75,
    esi_salary_threshold DECIMAL(15,2) NULL DEFAULT 21000.00,
    enable_tds TINYINT(1) NOT NULL DEFAULT 0,
    tds_rate DECIMAL(8,2) NOT NULL DEFAULT 10.00,
    tds_annual_threshold DECIMAL(15,2) NULL DEFAULT 700000.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_hr_payroll_policy_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE hr_payroll_items
    ADD COLUMN pf_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER bonus_amount,
    ADD COLUMN esi_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER pf_amount,
    ADD COLUMN tds_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER esi_amount,
    ADD COLUMN statutory_deduction_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER deduction_amount;
