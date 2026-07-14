<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorLog extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'visitor_name', 'purpose', 'host_staff_id', 'time_in', 'time_out', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['time_in' => 'datetime', 'time_out' => 'datetime'];
    }

    public function hostStaff(): BelongsTo { return $this->belongsTo(Staff::class, 'host_staff_id'); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }

    public function isCheckedOut(): bool { return $this->time_out !== null; }
}
