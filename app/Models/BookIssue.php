<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookIssue extends Model
{
    protected $fillable = [
        'book_id', 'student_id', 'issued_by',
        'issued_at', 'due_date', 'returned_at', 'fine_amount',
    ];

    protected $casts = [
        'issued_at'   => 'date',
        'due_date'    => 'date',
        'returned_at' => 'date',
    ];

    public function book(): BelongsTo    { return $this->belongsTo(Book::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function issuer(): BelongsTo  { return $this->belongsTo(User::class, 'issued_by'); }

    public function isOverdue(): bool
    {
        return $this->returned_at === null && now()->startOfDay()->gt($this->due_date);
    }

    public function daysLate(): int
    {
        if ($this->returned_at === null) return 0;
        $late = $this->due_date->diffInDays($this->returned_at, false);
        return max(0, (int) $late);
    }
}
