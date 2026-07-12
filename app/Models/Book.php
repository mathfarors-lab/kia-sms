<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToBranch;

class Book extends Model
{
    use BelongsToBranch;
    use SoftDeletes;

    protected $fillable = [
        'title', 'author', 'isbn', 'category',
        'total_copies', 'available_copies', 'cover_path',
    ];

    public function issues(): HasMany
    {
        return $this->hasMany(BookIssue::class);
    }

    public function isAvailable(): bool
    {
        return $this->available_copies > 0;
    }
}
