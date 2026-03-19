<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'gateway',
        'gateway_payment_id',
        'gateway_order_id',
        'gateway_signature',
        'amount',
        'currency',
        'status',
        'paid_at',
        'invoice_number',
        'receipt_url',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
