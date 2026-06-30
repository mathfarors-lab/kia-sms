<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Student extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'student_code',
        'name_en',
        'name_km',
        'gender',
        'date_of_birth',
        'photo',
        'address',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guardians(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_guardian', 'student_id', 'guardian_id')
                    ->withPivot('relation', 'is_primary')
                    ->withTimestamps();
    }

    public function primaryGuardian(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->guardians()->wherePivot('is_primary', true);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name_km ?: $this->name_en;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }
}
