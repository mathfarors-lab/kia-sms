<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDevelopmentLog extends Model
{
    protected $fillable = ['staff_id', 'title', 'provider', 'completed_date', 'hours', 'notes', 'added_by'];

    protected function casts(): array
    {
        return [
            'completed_date' => 'date',
            'hours' => 'decimal:2',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
