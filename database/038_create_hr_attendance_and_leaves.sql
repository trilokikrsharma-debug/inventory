-- HR phase 2: attendance and leave management tables.

CREATE TABLE IF NOT EXISTS hr_attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present','absent','half_day','late') NOT NULL DEFAULT 'present',
    check_in_time TIME NULL,
    check_out_time TIME NULL,
    note TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_hr_attendance_employee_day (company_id, employee_id, attendance_date),
    KEY idx_hr_attendance_day (company_id, attendance_date),
    KEY idx_hr_attendance_status (company_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hr_leave_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    leave_type ENUM('casual','sick','earned','unpaid','other') NOT NULL DEFAULT 'casual',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days_count INT UNSIGNED NOT NULL DEFAULT 1,
    reason TEXT NOT NULL,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    rejection_reason TEXT NULL,
    requested_by INT UNSIGNED NULL,
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_hr_leave_employee (company_id, employee_id),
    KEY idx_hr_leave_status (company_id, status),
    KEY idx_hr_leave_dates (company_id, start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
