<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/HrLeaveAccrualService.php';

class HrLeaveAccrualServiceTest extends BaseTestCase {
    public function testShouldProcessMonthWhenNoPreviousMonthExists(): void {
        $this->assertTrue(HrLeaveAccrualService::shouldProcessMonth(null, '2026-04'));
    }

    public function testShouldProcessMonthRejectsSameOrEarlierMonth(): void {
        $this->assertFalse(HrLeaveAccrualService::shouldProcessMonth('2026-04', '2026-04'));
        $this->assertFalse(HrLeaveAccrualService::shouldProcessMonth('2026-05', '2026-04'));
    }

    public function testApplyMonthlyAccrualAddsMonthlyDays(): void {
        $next = HrLeaveAccrualService::applyMonthlyAccrual([
            'opening_days' => 2,
            'accrued_days' => 3,
            'used_days' => 1,
        ], 1.5);

        $this->assertSame([
            'opening_days' => 2.0,
            'accrued_days' => 4.5,
            'used_days' => 1.0,
            'available_days' => 5.5,
        ], $next);
    }

    public function testApplyMonthlyAccrualRespectsCarryForwardCap(): void {
        $next = HrLeaveAccrualService::applyMonthlyAccrual([
            'opening_days' => 5,
            'accrued_days' => 4,
            'used_days' => 1,
        ], 3, 8);

        $this->assertSame([
            'opening_days' => 5.0,
            'accrued_days' => 4.0,
            'used_days' => 1.0,
            'available_days' => 8.0,
        ], $next);
    }
}
