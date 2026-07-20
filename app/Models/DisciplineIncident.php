<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DisciplineIncident extends Model
{
    use BelongsToBranch, LogsActivity;

    const TYPES = [
        'tardiness',
        'disruptive_behavior',
        'bullying',
        'property_damage',
        'dress_code',
        'academic_dishonesty',
        'other',
    ];

    protected $fillable = ['student_id', 'reported_by', 'incident_date', 'type', 'description', 'action_taken'];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
