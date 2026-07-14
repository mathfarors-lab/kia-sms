<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'staff_id', 'date', 'status', 'method', 'arrival_time', 'departure_time',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
