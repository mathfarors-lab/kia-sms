<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /** Holidays overlapping the given [start, end] range, inclusive. */
    public function scopeOverlapping($query, $start, $end)
    {
        return $query->where('start_date', '<=', $end)->where('end_date', '>=', $start);
    }
}
