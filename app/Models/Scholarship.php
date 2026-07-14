<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBranch;

class Scholarship extends Model
{
    use BelongsToBranch;
    protected $fillable = ['student_id', 'type', 'value', 'reason', 'is_active', 'is_sibling_discount'];

    protected function casts(): array
    {
        return ['value' => 'decimal:2', 'is_active' => 'boolean', 'is_sibling_discount' => 'boolean'];
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
