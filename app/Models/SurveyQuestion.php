<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyQuestion extends Model
{
    const TYPES = ['multiple_choice', 'rating_scale', 'free_text', 'yes_no'];

    protected $fillable = [
        'survey_id', 'order', 'type', 'question_text_en', 'question_text_km', 'options', 'required',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class, 'question_id');
    }
}
