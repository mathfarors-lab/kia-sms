<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssuedDocument extends Model
{
    use BelongsToBranch;

    const TYPE_ID_CARD         = 'id_card';
    const TYPE_ENROLLMENT_CERT = 'enrollment_cert';
    const TYPE_GRADUATION_CERT = 'graduation_cert';
    const TYPE_LEAVING_CERT    = 'leaving_cert';

    protected $fillable = ['student_id', 'staff_id', 'type', 'number', 'issued_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
