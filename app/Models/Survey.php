<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    use BelongsToBranch;

    const AUDIENCES = ['all', 'role', 'branch', 'class', 'section'];

    const STATUSES = ['draft', 'open', 'closed'];

    protected $fillable = [
        'title_en', 'title_km', 'description_en', 'description_km', 'created_by',
        'audience', 'target_id', 'is_anonymous', 'opens_at', 'closes_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(SurveyCompletion::class);
    }

    public function isOpenForSubmissions(): bool
    {
        if ($this->status !== 'open') {
            return false;
        }

        return $this->closes_at === null || $this->closes_at->isFuture();
    }
}
