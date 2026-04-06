<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/HrStatutoryPayrollService.php';

class HrStatutoryPayrollServiceTest extends BaseTestCase {
    public function testCalculateReturnsZeroWhenPoliciesDisabled(): void {
        $result = HrStatutoryPayrollService::calculate(50000, [
            'enable_pf' => 0,
            'enable_esi' => 0,
            'enable_tds' => 0,
        ]);

        $this->assertSame([
            'pf_amount' => 0.0,
            'esi_amount' => 0.0,
            'tds_amount' => 0.0,
            'statutory_deduction_amount' => 0.0,
        ], $result);
    }

    public function testCalculateAppliesPfWithSalaryCap(): void {
        $result = HrStatutoryPayrollService::calculate(50000, [
            'enable_pf' => 1,
            'pf_rate' => 12,
            'pf_salary_cap' => 15000,
            'enable_esi' => 0,
            'enable_tds' => 0,
        ]);

        $this->assertSame(1800.0, $result['pf_amount']);
        $this->assertSame(1800.0, $result['statutory_deduction_amount']);
    }

    public function testCalculateAppliesEsiOnlyBelowThreshold(): void {
        $result = HrStatutoryPayrollService::calculate(18000, [
            'enable_pf' => 0,
            'enable_esi' => 1,
            'esi_rate' => 0.75,
            'esi_salary_threshold' => 21000,
            'enable_tds' => 0,
        ]);

        $this->assertSame(135.0, $result['esi_amount']);
    }

    public function testCalculateAppliesTdsWhenAnnualizedSalaryExceedsThreshold(): void {
        $result = HrStatutoryPayrollService::calculate(80000, [
            'enable_pf' => 0,
            'enable_esi' => 0,
            'enable_tds' => 1,
            'tds_rate' => 10,
            'tds_annual_threshold' => 700000,
        ]);

        $this->assertSame(8000.0, $result['tds_amount']);
        $this->assertSame(8000.0, $result['statutory_deduction_amount']);
    }
}
