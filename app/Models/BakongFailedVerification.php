<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BakongFailedVerification extends Model
{
    protected $fillable = [
        'transaction_reference',
        'reason',
        'raw_payload',
        'raw_body',
        'received_signature',
        'replayed_at',
        'replay_result',
    ];

    protected $casts = [
        'raw_payload'  => 'array',
        'replayed_at'  => 'datetime',
    ];
}
