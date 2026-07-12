<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBranch;

class TermResult extends Model
{
    use BelongsToBranch;
    protected $fillable = [
        'academic_year_id', 'semester', 'student_id', 'section_id',
        'total', 'average', 'gpa', 'rank', 'result',
        'is_published', 'is_finalized', 'has_missing_marks', 'teacher_remark',
    ];

    protected function casts(): array
    {
        return [
            'is_published'      => 'boolean',
            'is_finalized'      => 'boolean',
            'has_missing_marks' => 'boolean',
        ];
    }

    public function academicYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function section(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function semesterLabel(): string
    {
        return match ($this->semester) {
            1       => __('term_results.semester_1'),
            2       => __('term_results.semester_2'),
            default => __('term_results.annual'),
        };
    }
}
