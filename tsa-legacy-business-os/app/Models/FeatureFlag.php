<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'key',
        'name',
        'description',
        'value_type',
        'default_value',
        'is_active',
    ];

    protected $casts = [
        'default_value' => 'array',
        'is_active' => 'boolean',
    ];

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_feature_flags')
            ->using(PlanFeature::class)
            ->withPivot(['value', 'is_enabled'])
            ->withTimestamps();
    }
}
