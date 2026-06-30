<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id', 'subject_id', 'teacher_id',
        'day', 'period', 'start_time', 'end_time', 'room',
    ];

    public static function hasConflict(int $sectionId, string $day, int $period, ?int $excludeId = null): bool
    {
        $sectionQuery = static::where('section_id', $sectionId)
            ->where('day', $day)
            ->where('period', $period);

        if ($excludeId) {
            $sectionQuery->where('id', '!=', $excludeId);
        }

        return $sectionQuery->exists();
    }

    public static function hasTeacherConflict(int $teacherId, string $day, int $period, ?int $excludeId = null): bool
    {
        $query = static::where('teacher_id', $teacherId)
            ->where('day', $day)
            ->where('period', $period);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function section(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Staff::class, 'teacher_id');
    }
}
