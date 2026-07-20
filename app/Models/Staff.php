<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Staff extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    const CONTRACT_TYPES = ['full_time', 'part_time', 'contract'];

    const EMPLOYMENT_STATUSES = ['active', 'on_leave', 'terminated'];

    protected $fillable = [
        'user_id',
        'staff_code',
        'position',
        'department',
        'photo',
        'joined_at',
        'salary',
        'contract_type',
        'contract_end_date',
        'employment_status',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'salary' => 'decimal:2',
            'contract_end_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        // salary excluded — sensitive compensation data must not appear in the audit log viewer
        return LogOptions::defaults()
            ->logOnly([
                'user_id', 'staff_code', 'position', 'department', 'photo', 'joined_at',
                'contract_type', 'contract_end_date', 'employment_status',
            ])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Sections where this staff member is the homeroom (class) teacher. */
    public function homeroomSections(): HasMany
    {
        return $this->hasMany(Section::class, 'class_teacher_id');
    }

    public function issuedDocuments(): HasMany
    {
        return $this->hasMany(IssuedDocument::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(StaffQualification::class)->orderByDesc('year');
    }

    /** Supporting files (CV etc.) uploaded to this staff record — distinct from issuedDocuments(). */
    public function documents(): HasMany
    {
        return $this->hasMany(StaffDocument::class)->latest();
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(StaffEvaluation::class)->latest('evaluation_date');
    }

    public function developmentLogs(): HasMany
    {
        return $this->hasMany(StaffDevelopmentLog::class)->latest('completed_date');
    }
}
