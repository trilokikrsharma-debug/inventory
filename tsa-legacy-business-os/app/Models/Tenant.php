<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;
    use UsesCentralConnection;

    protected static function booted(): void
    {
        static::creating(function (self $tenant): void {
            $current = (string) ($tenant->getInternal('db_connection') ?? '');

            if ($current !== '') {
                return;
            }

            $template = (string) config(
                'tenancy.database.template_tenant_connection',
                config('tenancy.database.central_connection', 'tenant')
            );

            if ($template === '') {
                $template = 'tenant';
            }

            $tenant->setInternal('db_connection', $template);
        });
    }

    protected $fillable = [
        'id',
        'name',
        'slug',
        'email',
        'phone',
        'status',
        'owner_user_id',
        'trial_ends_at',
        'onboarded_at',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'trial_ends_at' => 'datetime',
        'onboarded_at' => 'datetime',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'email',
            'phone',
            'status',
            'owner_user_id',
            'trial_ends_at',
            'onboarded_at',
            'created_at',
            'updated_at',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
                Subscription::STATUS_PAST_DUE,
            ])
            ->latestOfMany('ends_at');
    }

    public function hasFeature(string $feature, mixed $default = false): bool
    {
        $subscription = $this->activeSubscription;

        if (! $subscription || ! $subscription->plan) {
            return (bool) $default;
        }

        return $subscription->plan->featureEnabled($feature, $default);
    }
}
