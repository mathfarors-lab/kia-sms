<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AdmissionApplication extends Model
{
    use LogsActivity;

    public const STATUSES = ['enquiry', 'applied', 'under_review', 'accepted', 'rejected', 'converted'];

    protected $fillable = [
        'application_no',
        'name_en', 'name_km', 'gender', 'date_of_birth', 'address',
        'guardian_name', 'guardian_phone', 'guardian_relation',
        'desired_class_id', 'academic_year_id',
        'status', 'notes',
        'document_path', 'document_original_name',
        'reviewed_by', 'reviewed_at', 'student_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'reviewed_at'   => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'reviewed_by', 'student_id'])
            ->logOnlyDirty();
    }

    public function desiredClass(): BelongsTo  { return $this->belongsTo(SchoolClass::class, 'desired_class_id'); }
    public function academicYear(): BelongsTo  { return $this->belongsTo(AcademicYear::class); }
    public function reviewer(): BelongsTo      { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function student(): BelongsTo       { return $this->belongsTo(Student::class); }

    public function isConverted(): bool { return $this->status === 'converted' || $this->student_id !== null; }
}
