<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyCompletion extends Model
{
    // The table has only completed_at, no created_at/updated_at — this is a
    // deliberately minimal tracking row, not an auto-timestamped record.
    public $timestamps = false;

    protected $fillable = ['survey_id', 'user_id', 'completed_at'];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
