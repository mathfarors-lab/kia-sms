<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkSubmission extends Model
{
    protected $fillable = [
        'homework_id', 'student_id',
        'file_path', 'file_original_name', 'note',
        'is_late', 'submitted_at',
        'grade', 'feedback', 'graded_by', 'graded_at',
    ];

    protected $casts = [
        'is_late'    => 'boolean',
        'submitted_at' => 'datetime',
        'graded_at'  => 'datetime',
    ];

    public function homework(): BelongsTo  { return $this->belongsTo(Homework::class); }
    public function student(): BelongsTo   { return $this->belongsTo(Student::class); }
    public function grader(): BelongsTo    { return $this->belongsTo(Staff::class, 'graded_by'); }
}
