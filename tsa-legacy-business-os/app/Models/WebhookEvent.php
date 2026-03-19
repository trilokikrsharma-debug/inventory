<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'signature',
        'payload_hash',
        'payload',
        'processed_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
