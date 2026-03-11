<?php
/**
 * SaaS Billing Helper
 *
 * Shared helpers for trusted amount math, promo/referral checks,
 * and small normalization routines used across billing modules.
 */
class SaaSBillingHelper {

    public const MIN_PAYABLE = 1.00;

    /**
     * Normalize any numeric input into a safe 2-decimal amount.
     */
    public static function money($value): float {
        if ($value === null || $value === '') {
            return 0.00;
        }
        $num = (float)$value;
        if (!is_finite($num) || $num < 0) {
            $num = 0.00;
        }
        return (float)number_format($num, 2, '.', '');
    }

    /**
     * Convert decimal amount to paise.
     */
    public static function toPaise($amount): int {
        return (int)round(self::money($amount) * 100);
    }

    /**
     * Return final plan price using offer_price when valid.
     */
    public static function effectivePlanPrice(array $plan): float {
        $price = self::money($plan['price'] ?? 0);
        $offer = self::money($plan['offer_price'] ?? 0);

        if ($offer > 0 && $offer < $price) {
            return $offer;
        }
        return $price;
    }

    /**
     * Calculate discount amount from promo inputs.
     */
    public static function discountAmount(
        float $baseAmount,
        string $discountType,
        float $discountValue,
        ?float $maxDiscountAmount = null
    ): float {
        $baseAmount = self::money($baseAmount);
        $discountValue = self::money($discountValue);
        $maxDiscountAmount = $maxDiscountAmount !== null ? self::money($maxDiscountAmount) : null;

        if ($baseAmount <= 0 || $discountValue <= 0) {
            return 0.00;
        }

        $discount = 0.00;
        if ($discountType === 'percentage') {
            $discount = self::money(($baseAmount * $discountValue) / 100);
        } elseif ($discountType === 'fixed') {
            $discount = min($discountValue, $baseAmount);
        }

        if ($maxDiscountAmount !== null && $maxDiscountAmount > 0) {
            $discount = min($discount, $maxDiscountAmount);
        }

        return self::money(max(0, min($discount, $baseAmount)));
    }

    /**
     * Apply discount with floor rules (>= Rs 1 by default).
     */
    public static function finalPayable(float $baseAmount, float $discountAmount, bool $allowBelowOne = false): float {
        $baseAmount = self::money($baseAmount);
        $discountAmount = self::money($discountAmount);
        $final = self::money($baseAmount - $discountAmount);

        if (!$allowBelowOne) {
            $final = max(self::MIN_PAYABLE, $final);
        } else {
            $final = max(0, $final);
        }
        return self::money($final);
    }

    /**
     * Parse plan IDs from JSON/csv into int array.
     */
    public static function parsePlanIds($raw): array {
        if (is_array($raw)) {
            $ids = $raw;
        } else {
            $raw = trim((string)$raw);
            if ($raw === '') {
                return [];
            }

            $json = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $ids = $json;
            } else {
                $ids = array_map('trim', explode(',', $raw));
            }
        }

        $clean = [];
        foreach ($ids as $id) {
            $n = (int)$id;
            if ($n > 0) {
                $clean[$n] = $n;
            }
        }
        return array_values($clean);
    }

    /**
     * Check if promo is applicable to a specific plan.
     */
    public static function isPromoApplicableToPlan(array $promo, int $planId): bool {
        $ids = self::parsePlanIds($promo['applicable_plan_ids'] ?? '');
        if (empty($ids)) {
            return true; // Empty means all plans.
        }
        return in_array($planId, $ids, true);
    }

    /**
     * Make URL-safe slug.
     */
    public static function slugify(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');
        return $value !== '' ? $value : 'plan';
    }

    /**
     * Current DB datetime string.
     */
    public static function now(): string {
        return date(DATETIME_FORMAT_DB);
    }

    /**
     * Validate date string in Y-m-d format.
     */
    public static function validDate(?string $date): bool {
        if ($date === null || $date === '') {
            return false;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        return strtotime($date) !== false;
    }

    /**
     * Generate unique-looking order code.
     */
    public static function generateOrderCode(string $prefix = 'SUB'): string {
        return strtoupper($prefix) . '-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}

