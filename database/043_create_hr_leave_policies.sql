-- HR phase 6: company-level leave accrual policies.

CREATE TABLE IF NOT EXISTS hr_leave_policies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    leave_type ENUM('casual','sick','earned','unpaid','other') NOT NULL,
    monthly_accrual_days DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    max_carry_forward DECIMAL(8,2) NULL,
    effective_from DATE NOT NULL,
    last_processed_month CHAR(7) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_hr_leave_policy_type (company_id, leave_type),
    KEY idx_hr_leave_policy_active (company_id, is_active, effective_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO hr_leave_policies (company_id, leave_type, monthly_accrual_days, max_carry_forward, effective_from, is_active)
SELECT c.id, 'casual', 1.00, 12.00, DATE_FORMAT(CURDATE(), '%Y-01-01'), 1
FROM companies c
WHERE NOT EXISTS (
    SELECT 1
    FROM hr_leave_policies p
    WHERE p.company_id = c.id
      AND p.leave_type = 'casual'
);

INSERT INTO hr_leave_policies (company_id, leave_type, monthly_accrual_days, max_carry_forward, effective_from, is_active)
SELECT c.id, 'earned', 1.50, 30.00, DATE_FORMAT(CURDATE(), '%Y-01-01'), 1
FROM companies c
WHERE NOT EXISTS (
    SELECT 1
    FROM hr_leave_policies p
    WHERE p.company_id = c.id
      AND p.leave_type = 'earned'
);

INSERT INTO hr_leave_policies (company_id, leave_type, monthly_accrual_days, max_carry_forward, effective_from, is_active)
SELECT c.id, 'sick', 0.50, 10.00, DATE_FORMAT(CURDATE(), '%Y-01-01'), 1
FROM companies c
WHERE NOT EXISTS (
    SELECT 1
    FROM hr_leave_policies p
    WHERE p.company_id = c.id
      AND p.leave_type = 'sick'
);
