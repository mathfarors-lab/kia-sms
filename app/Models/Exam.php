<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Exam extends Model
{
    use LogsActivity;

    protected $fillable = ['academic_year_id', 'name', 'type', 'semester', 'weight', 'is_published'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'weight'       => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function academicYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function marks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamMark::class);
    }

    public function results(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
