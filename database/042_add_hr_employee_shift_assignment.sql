-- HR phase 5: employee-level shift assignment for attendance context.

ALTER TABLE hr_employees
    ADD COLUMN shift_id INT UNSIGNED NULL AFTER phone,
    ADD KEY idx_hr_employees_shift (company_id, shift_id);

UPDATE hr_employees e
JOIN (
    SELECT company_id, MIN(id) AS default_shift_id
    FROM hr_shifts
    WHERE is_default = 1
    GROUP BY company_id
) s ON s.company_id = e.company_id
SET e.shift_id = s.default_shift_id
WHERE e.shift_id IS NULL;
