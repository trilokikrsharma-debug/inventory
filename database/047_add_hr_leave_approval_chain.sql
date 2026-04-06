-- HR phase 10: reporting manager mapping and leave approval chain.

ALTER TABLE hr_employees
    ADD COLUMN reporting_manager_user_id INT UNSIGNED NULL AFTER shift_id,
    ADD KEY idx_hr_employees_manager (company_id, reporting_manager_user_id);

ALTER TABLE hr_leave_requests
    ADD COLUMN approver_user_id INT UNSIGNED NULL AFTER requested_by,
    ADD COLUMN manager_status ENUM('pending','approved','rejected','not_required') NOT NULL DEFAULT 'not_required' AFTER approver_user_id,
    ADD COLUMN manager_approved_by INT UNSIGNED NULL AFTER manager_status,
    ADD COLUMN manager_approved_at DATETIME NULL AFTER manager_approved_by,
    ADD COLUMN manager_rejection_reason TEXT NULL AFTER manager_approved_at,
    ADD KEY idx_hr_leave_manager_status (company_id, manager_status, status);
