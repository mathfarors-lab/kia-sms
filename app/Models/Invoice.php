<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'number', 'student_id', 'academic_year_id', 'term',
        'subtotal', 'discount', 'total', 'paid', 'status', 'due_date',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'  => 'decimal:2',
            'discount'  => 'decimal:2',
            'total'     => 'decimal:2',
            'paid'      => 'decimal:2',
            'due_date'  => 'date',
        ];
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function remainingBalance(): string
    {
        return bcsub((string) $this->total, (string) $this->paid, 2);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['unpaid', 'partial', 'overdue']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }
}
