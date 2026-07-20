<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Student extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_guardian', 'student_id', 'guardian_id')
            ->withPivot('relation', 'is_primary')
            ->withTimestamps();
    }

    public function primaryGuardian(): BelongsToMany
    {
        return $this->guardians()->wherePivot('is_primary', true);
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'student_section')
            ->withPivot('academic_year_id')
            ->withTimestamps();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scholarships(): HasMany
    {
        return $this->hasMany(Scholarship::class);
    }

    public function examMarks(): HasMany
    {
        return $this->hasMany(ExamMark::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function issuedDocuments(): HasMany
    {
        return $this->hasMany(IssuedDocument::class);
    }

    /** Supporting files uploaded to this student's record (ID scans, medical records, etc.) — distinct from issuedDocuments(), which the school generates and issues. */
    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class)->latest();
    }

    /** The admission application this student was converted from, if any (direct creates have none). */
    public function admissionApplication(): HasOne
    {
        return $this->hasOne(AdmissionApplication::class);
    }

    public function disciplineIncidents(): HasMany
    {
        return $this->hasMany(DisciplineIncident::class)->latest('incident_date');
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
