-- Payroll adjustments for enterprise salary processing.

ALTER TABLE hr_payroll_items
    ADD COLUMN allowance_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER gross_salary,
    ADD COLUMN bonus_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER allowance_amount,
    ADD COLUMN other_deduction_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER deduction_amount,
    ADD COLUMN adjustment_notes TEXT NULL AFTER net_salary;
