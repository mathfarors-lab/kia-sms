<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StaffQualification extends Model
{
    use LogsActivity;

    protected $fillable = ['staff_id', 'degree', 'institution', 'year'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
