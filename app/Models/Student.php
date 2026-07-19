<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Concerns\BelongsToBranch;

class Student extends Model
{
    use BelongsToBranch;
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

    public function sections(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'student_section')
                    ->withPivot('academic_year_id')
                    ->withTimestamps();
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scholarships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Scholarship::class);
    }

    public function examMarks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamMark::class);
    }

    public function examResults(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function issuedDocuments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(IssuedDocument::class);
    }

    /** Supporting files uploaded to this student's record (ID scans, medical records, etc.) — distinct from issuedDocuments(), which the school generates and issues. */
    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentDocument::class)->latest();
    }

    /** The admission application this student was converted from, if any (direct creates have none). */
    public function admissionApplication(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AdmissionApplication::class);
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
