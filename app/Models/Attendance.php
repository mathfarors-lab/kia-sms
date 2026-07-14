<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Concerns\BelongsToBranch;

class Attendance extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity;

    protected $fillable = [
        'student_id', 'section_id', 'date', 'status', 'marked_by', 'remark',
        'method', 'arrival_time', 'departure_time',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function scopeForDate($q, $date)
    {
        $q->whereDate('date', $date);
    }

    public function scopeForSection($q, $sectionId)
    {
        $q->where('section_id', $sectionId);
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function section(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function markedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
