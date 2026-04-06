-- HR phase 4: holiday calendar, shift master, and leave balance ledger.

CREATE TABLE IF NOT EXISTS hr_holidays (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    holiday_date DATE NOT NULL,
    holiday_name VARCHAR(150) NOT NULL,
    holiday_type ENUM('public','optional','company') NOT NULL DEFAULT 'public',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_hr_holidays_day (company_id, holiday_date, holiday_name),
    KEY idx_hr_holidays_date (company_id, holiday_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hr_shifts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    shift_name VARCHAR(120) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    weekly_off_day VARCHAR(20) NOT NULL DEFAULT 'Sunday',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_hr_shifts_default (company_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hr_leave_balances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    leave_type ENUM('casual','sick','earned','unpaid','other') NOT NULL DEFAULT 'casual',
    opening_days DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    accrued_days DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    used_days DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    available_days DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_hr_leave_balance_employee_type (company_id, employee_id, leave_type),
    KEY idx_hr_leave_balance_employee (company_id, employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
