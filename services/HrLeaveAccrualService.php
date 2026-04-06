<?php
/**
 * Leave accrual calculation helpers.
 */
class HrLeaveAccrualService {
    public static function shouldProcessMonth(?string $lastProcessedMonth, string $targetMonth): bool {
        if (!preg_match('/^\d{4}-\d{2}$/', $targetMonth)) {
            throw new \RuntimeException('Target accrual month must be in YYYY-MM format.');
        }

        if ($lastProcessedMonth === null || $lastProcessedMonth === '') {
            return true;
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $lastProcessedMonth)) {
            return true;
        }

        return strcmp($lastProcessedMonth, $targetMonth) < 0;
    }

    public static function applyMonthlyAccrual(array $balance, float $monthlyAccrualDays, ?float $maxCarryForward = null): array {
        $opening = round((float)($balance['opening_days'] ?? 0), 2);
        $accrued = round((float)($balance['accrued_days'] ?? 0), 2);
        $used = round((float)($balance['used_days'] ?? 0), 2);
        $monthlyAccrualDays = round(max(0, $monthlyAccrualDays), 2);

        $newAccrued = round($accrued + $monthlyAccrualDays, 2);
        $available = round($opening + $newAccrued - $used, 2);

        if ($maxCarryForward !== null) {
            $maxCarryForward = round(max(0, $maxCarryForward), 2);
            if ($available > $maxCarryForward) {
                $available = $maxCarryForward;
                $newAccrued = round(max(0, $available + $used - $opening), 2);
            }
        }

        return [
            'opening_days' => $opening,
            'accrued_days' => $newAccrued,
            'used_days' => $used,
            'available_days' => $available,
        ];
    }
}
