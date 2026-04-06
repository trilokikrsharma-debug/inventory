<?php
/**
 * Statutory payroll deduction helpers.
 */
class HrStatutoryPayrollService {
    public static function calculate(float $grossSalary, array $policy): array {
        $grossSalary = round(max(0, $grossSalary), 2);

        $pfAmount = 0.0;
        if (!empty($policy['enable_pf'])) {
            $pfBase = $grossSalary;
            if (isset($policy['pf_salary_cap']) && $policy['pf_salary_cap'] !== null) {
                $pfBase = min($pfBase, round((float)$policy['pf_salary_cap'], 2));
            }
            $pfAmount = round($pfBase * ((float)($policy['pf_rate'] ?? 0) / 100), 2);
        }

        $esiAmount = 0.0;
        if (!empty($policy['enable_esi'])) {
            $esiThreshold = round((float)($policy['esi_salary_threshold'] ?? 0), 2);
            if ($esiThreshold <= 0 || $grossSalary <= $esiThreshold) {
                $esiAmount = round($grossSalary * ((float)($policy['esi_rate'] ?? 0) / 100), 2);
            }
        }

        $tdsAmount = 0.0;
        if (!empty($policy['enable_tds'])) {
            $annualizedGross = round($grossSalary * 12, 2);
            $tdsThreshold = round((float)($policy['tds_annual_threshold'] ?? 0), 2);
            if ($tdsThreshold <= 0 || $annualizedGross > $tdsThreshold) {
                $tdsAmount = round($grossSalary * ((float)($policy['tds_rate'] ?? 0) / 100), 2);
            }
        }

        return [
            'pf_amount' => $pfAmount,
            'esi_amount' => $esiAmount,
            'tds_amount' => $tdsAmount,
            'statutory_deduction_amount' => round($pfAmount + $esiAmount + $tdsAmount, 2),
        ];
    }
}
