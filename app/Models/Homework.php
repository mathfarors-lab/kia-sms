<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToBranch;

class Homework extends Model
{
    use BelongsToBranch;
    protected $fillable = [
        'section_id', 'subject_id', 'teacher_id',
        'title', 'description', 'attachment_path', 'attachment_original_name',
        'due_date', 'published_at',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'published_at' => 'datetime',
    ];

    public function section(): BelongsTo  { return $this->belongsTo(Section::class); }
    public function subject(): BelongsTo  { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo  { return $this->belongsTo(Staff::class, 'teacher_id'); }
    public function submissions(): HasMany { return $this->hasMany(HomeworkSubmission::class); }

    public function isLate(\DateTimeInterface|string $submittedAt): bool
    {
        return Carbon::parse($submittedAt)->startOfDay()->gt(
            Carbon::parse($this->due_date)->endOfDay()
        );
    }
}
