-- HR phase 8: shift grace period for automatic late marking.

ALTER TABLE hr_shifts
    ADD COLUMN grace_period_minutes INT UNSIGNED NOT NULL DEFAULT 15 AFTER end_time;
