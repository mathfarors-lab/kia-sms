<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StaffEvaluation extends Model
{
    use BelongsToBranch, LogsActivity;

    const STATUS_DRAFT = 'draft';

    const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'staff_id', 'evaluated_by', 'evaluation_date', 'overall_rating',
        'strengths', 'areas_for_improvement', 'comments', 'status', 'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'evaluation_date' => 'date',
            'finalized_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'evaluated_by', 'overall_rating'])
            ->logOnlyDirty();
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }
}
