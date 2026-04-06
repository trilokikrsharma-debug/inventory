-- HR phase 3: payroll runs and employee payroll items.

CREATE TABLE IF NOT EXISTS hr_payroll_runs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    payroll_month CHAR(7) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    employee_count INT UNSIGNED NOT NULL DEFAULT 0,
    gross_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    deduction_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    net_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('processed','paid') NOT NULL DEFAULT 'processed',
    processed_by INT UNSIGNED NULL,
    processed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_hr_payroll_month (company_id, payroll_month),
    KEY idx_hr_payroll_status (company_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hr_payroll_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    payroll_run_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    attendance_units DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    approved_leave_days INT UNSIGNED NOT NULL DEFAULT 0,
    gross_salary DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    deduction_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    net_salary DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_hr_payroll_item_employee (company_id, payroll_run_id, employee_id),
    KEY idx_hr_payroll_item_payment (company_id, payment_status),
    KEY idx_hr_payroll_item_run (company_id, payroll_run_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
