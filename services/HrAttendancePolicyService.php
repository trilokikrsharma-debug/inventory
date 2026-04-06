<?php
/**
 * Attendance policy helpers.
 */
class HrAttendancePolicyService {
    public static function resolveStatus(string $submittedStatus, ?string $checkInTime, ?string $shiftStartTime, ?int $gracePeriodMinutes): array {
        $submittedStatus = strtolower(trim($submittedStatus));
        if ($submittedStatus !== 'present') {
            return ['status' => $submittedStatus, 'label' => null];
        }

        if (empty($checkInTime) || empty($shiftStartTime)) {
            return ['status' => $submittedStatus, 'label' => null];
        }

        $cutoff = self::cutoffTime($shiftStartTime, $gracePeriodMinutes ?? 0);
        if ($cutoff === null) {
            return ['status' => $submittedStatus, 'label' => null];
        }

        $checkInNormalized = substr((string)$checkInTime, 0, 5);
        if ($checkInNormalized > $cutoff) {
            return [
                'status' => 'late',
                'label' => '[Auto Late: Shift cutoff ' . $cutoff . ']',
            ];
        }

        return ['status' => $submittedStatus, 'label' => null];
    }

    public static function cutoffTime(string $shiftStartTime, int $gracePeriodMinutes): ?string {
        $shiftStartTime = substr(trim($shiftStartTime), 0, 5);
        if (!preg_match('/^\d{2}:\d{2}$/', $shiftStartTime)) {
            return null;
        }

        $gracePeriodMinutes = max(0, $gracePeriodMinutes);
        $base = strtotime('2000-01-01 ' . $shiftStartTime . ':00');
        if ($base === false) {
            return null;
        }

        return date('H:i', $base + ($gracePeriodMinutes * 60));
    }
}
