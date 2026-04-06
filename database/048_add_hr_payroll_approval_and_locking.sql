-- Payroll approval and lock/freeze workflow.

ALTER TABLE hr_payroll_runs
    MODIFY COLUMN status ENUM('processed','approved','paid') NOT NULL DEFAULT 'processed',
    ADD COLUMN approved_by INT UNSIGNED NULL AFTER processed_by,
    ADD COLUMN approved_at DATETIME NULL AFTER approved_by,
    ADD COLUMN locked_by INT UNSIGNED NULL AFTER approved_at,
    ADD COLUMN locked_at DATETIME NULL AFTER locked_by;

