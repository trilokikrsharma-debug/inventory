<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'currency',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(FeatureFlag::class, 'plan_feature_flags')
            ->using(PlanFeature::class)
            ->withPivot(['value', 'is_enabled'])
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function featureEnabled(string $key, mixed $default = false): bool
    {
        $feature = $this->features->firstWhere('key', $key);

        if (! $feature) {
            return (bool) $default;
        }

        return (bool) ($feature->pivot->is_enabled ?? false);
    }

    public function featureValue(string $key, mixed $default = null): mixed
    {
        $feature = $this->features->firstWhere('key', $key);

        if (! $feature) {
            return $default;
        }

        return $feature->pivot->value ?? $default;
    }
}
