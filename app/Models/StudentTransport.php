<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransport extends Model
{
    protected $table = 'student_transport';

    protected $fillable = ['student_id', 'vehicle_id', 'route_id', 'academic_year_id', 'enrolled_at'];

    protected $casts = ['enrolled_at' => 'datetime'];

    public function student(): BelongsTo      { return $this->belongsTo(Student::class); }
    public function vehicle(): BelongsTo      { return $this->belongsTo(Vehicle::class); }
    public function route(): BelongsTo        { return $this->belongsTo(TransportRoute::class, 'route_id'); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
}
