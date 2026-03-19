<?php

namespace App\Services\Saas;

use App\Models\Subscription;
use Illuminate\Validation\ValidationException;

class PlanLimitService
{
    /**
     * @var array<string, int|null>
     */
    private array $limitCache = [];

    public function limitForTenant(string $tenantId, string $featureKey): ?int
    {
        $cacheKey = $tenantId.'|'.$featureKey;

        if (array_key_exists($cacheKey, $this->limitCache)) {
            return $this->limitCache[$cacheKey];
        }

        $subscription = Subscription::query()
            ->with('plan.features')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
                Subscription::STATUS_PAST_DUE,
            ])
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->latest('id')
            ->first();

        if (! $subscription || ! $subscription->plan) {
            return $this->limitCache[$cacheKey] = null;
        }

        $feature = $subscription->plan->features->firstWhere('key', $featureKey);

        if (! $feature || ! ($feature->pivot->is_enabled ?? false)) {
            return $this->limitCache[$cacheKey] = null;
        }

        $value = data_get($feature->pivot->value, 'value');

        if (! is_numeric($value)) {
            return $this->limitCache[$cacheKey] = null;
        }

        $limit = (int) $value;

        return $this->limitCache[$cacheKey] = $limit > 0 ? $limit : null;
    }

    public function enforce(string $tenantId, string $featureKey, int $currentCount, string $resourceLabel): void
    {
        $limit = $this->limitForTenant($tenantId, $featureKey);

        if ($limit === null) {
            return;
        }

        if ($currentCount >= $limit) {
            throw ValidationException::withMessages([
                'plan_limit' => "Plan limit reached for {$resourceLabel}. Current plan allows up to {$limit} {$resourceLabel}.",
            ]);
        }
    }
}