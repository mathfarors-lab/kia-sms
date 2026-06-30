<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeScale extends Model
{
    protected $fillable = ['grade', 'min_score', 'max_score', 'gpa', 'remark_en', 'remark_km'];

    protected function casts(): array
    {
        return ['gpa' => 'decimal:2'];
    }

    public static function resolveFor(float $score): ?self
    {
        return static::where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderByDesc('min_score')
            ->first();
    }
}
