<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PlanFeature extends Pivot
{
    use UsesCentralConnection;

    protected $table = 'plan_feature_flags';

    protected $fillable = [
        'plan_id',
        'feature_flag_id',
        'is_enabled',
        'value',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'value' => 'array',
    ];
}
