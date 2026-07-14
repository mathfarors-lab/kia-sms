<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GateScanLog extends Model
{
    use BelongsToBranch;

    public const RESULTS = ['matched', 'unmatched', 'duplicate', 'wrong_branch'];

    protected $fillable = [
        'scanned_code', 'student_id', 'staff_id', 'result', 'event', 'scanned_by', 'scanned_at',
    ];

    protected function casts(): array
    {
        return ['scanned_at' => 'datetime'];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function staff(): BelongsTo   { return $this->belongsTo(Staff::class); }
    public function scannedBy(): BelongsTo { return $this->belongsTo(User::class, 'scanned_by'); }
}
