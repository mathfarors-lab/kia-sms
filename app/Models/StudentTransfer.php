<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToBranch;

class StudentTransfer extends Model
{
    use BelongsToBranch;

    const TYPE_TRANSFER   = 'transfer';
    const TYPE_WITHDRAWAL = 'withdrawal';

    const REASON_CATEGORIES = ['relocation', 'financial', 'academic_fit', 'disciplinary', 'health', 'other'];

    protected $fillable = [
        'student_id', 'type', 'reason_category', 'reason_note', 'effective_date',
        'destination_branch_id', 'destination_name', 'outstanding_balance_at_time', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date'              => 'date',
            'outstanding_balance_at_time' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
