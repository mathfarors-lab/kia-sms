<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentIntent extends Model
{
    protected $fillable = [
        'invoice_id', 'qr_string', 'md5_hash', 'bill_number',
        'amount', 'currency', 'expires_at', 'status',
        'bakong_hash', 'error_reason', 'polled_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'polled_at'  => 'datetime',
        'amount'     => 'string', // keep string for bcmath
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** Pending intents that have not yet expired — the set to poll. */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending')
                     ->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
