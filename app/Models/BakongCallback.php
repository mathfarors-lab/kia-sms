<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BakongCallback extends Model
{
    protected $fillable = [
        'transaction_reference', 'invoice_id', 'amount', 'currency',
        'status', 'payer_account', 'raw_payload', 'signature_valid', 'flag_reason',
    ];

    protected $casts = [
        'raw_payload'      => 'array',
        'signature_valid'  => 'boolean',
        'amount'           => 'string', // keep as string for bcmath
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
