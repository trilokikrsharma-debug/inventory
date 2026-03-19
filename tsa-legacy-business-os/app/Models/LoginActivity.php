<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class LoginActivity extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'email',
        'status',
        'ip_address',
        'user_agent',
        'attempted_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
