<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = ['route_id', 'plate_no', 'driver_name', 'driver_phone', 'capacity'];

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function studentTransports(): HasMany
    {
        return $this->hasMany(StudentTransport::class);
    }

    public function enrolledCount(int $academicYearId): int
    {
        return $this->studentTransports()->where('academic_year_id', $academicYearId)->count();
    }
}
