<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToBranch;

class Leave extends Model
{
    use BelongsToBranch;
    protected $fillable = [
        'user_id', 'type', 'start_date', 'end_date',
        'reason', 'status', 'reviewed_by', 'reviewer_note', 'reviewed_at',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function overlaps(int $userId, string $start, string $end, ?int $excludeId = null): bool
    {
        return static::where('user_id', $userId)
            ->where('status', '!=', 'rejected')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();
    }
}
