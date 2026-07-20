<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Concerns\BelongsToBranch;

class FeedbackItem extends Model
{
    use BelongsToBranch, LogsActivity;

    const CATEGORIES = ['academic', 'facility', 'staff_conduct', 'billing', 'other'];
    const STATUSES    = ['open', 'in_progress', 'resolved', 'closed'];

    /**
     * Forward transitions reachable through the normal status-update action.
     * Deliberately excludes "open" as a target anywhere — going back to open
     * from resolved/closed is only possible through reopen(), never here.
     */
    const TRANSITIONS = [
        'open'        => ['in_progress', 'resolved', 'closed'],
        'in_progress' => ['resolved', 'closed'],
        'resolved'    => ['closed'],
        'closed'      => [],
    ];

    protected $fillable = [
        'submitted_by', 'student_id', 'category', 'subject', 'body',
        'status', 'assigned_to', 'attachment_path', 'attachment_original_name',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'assigned_to'])
            ->logOnlyDirty();
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(FeedbackReply::class)->oldest();
    }

    /** Whether the normal (non-reopen) status action may move to $newStatus from here. */
    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function canReopen(): bool
    {
        return in_array($this->status, ['resolved', 'closed'], true);
    }
}
