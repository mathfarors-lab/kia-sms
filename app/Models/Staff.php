<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Staff extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'staff_code',
        'position',
        'department',
        'photo',
        'joined_at',
        'salary',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        // salary excluded — sensitive compensation data must not appear in the audit log viewer
        return LogOptions::defaults()
            ->logOnly(['user_id', 'staff_code', 'position', 'department', 'photo', 'joined_at'])
            ->logOnlyDirty();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Sections where this staff member is the homeroom (class) teacher. */
    public function homeroomSections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Section::class, 'class_teacher_id');
    }
}
