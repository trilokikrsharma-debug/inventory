<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/HrAttendancePolicyService.php';

class HrAttendancePolicyServiceTest extends BaseTestCase {
    public function testCutoffTimeAddsGracePeriod(): void {
        $this->assertSame('09:15', HrAttendancePolicyService::cutoffTime('09:00:00', 15));
    }

    public function testResolveStatusLeavesNonPresentStatusUntouched(): void {
        $result = HrAttendancePolicyService::resolveStatus('absent', '09:40', '09:00:00', 15);

        $this->assertSame('absent', $result['status']);
        $this->assertNull($result['label']);
    }

    public function testResolveStatusAutoMarksLateWhenBeyondCutoff(): void {
        $result = HrAttendancePolicyService::resolveStatus('present', '09:21', '09:00:00', 15);

        $this->assertSame('late', $result['status']);
        $this->assertSame('[Auto Late: Shift cutoff 09:15]', $result['label']);
    }

    public function testResolveStatusKeepsPresentWithinGraceWindow(): void {
        $result = HrAttendancePolicyService::resolveStatus('present', '09:10', '09:00:00', 15);

        $this->assertSame('present', $result['status']);
        $this->assertNull($result['label']);
    }
}
