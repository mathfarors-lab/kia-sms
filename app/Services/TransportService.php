<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentTransport;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransportService
{
    public function assign(Student $student, Vehicle $vehicle, AcademicYear $year): StudentTransport
    {
        return DB::transaction(function () use ($student, $vehicle, $year) {
            // Lock vehicle row to prevent race on capacity check
            $vehicle = Vehicle::lockForUpdate()->findOrFail($vehicle->id);

            $enrolled = $vehicle->enrolledCount($year->id);
            if ($enrolled >= $vehicle->capacity) {
                throw ValidationException::withMessages([
                    'vehicle_id' => [__('engagement.vehicle_full', ['capacity' => $vehicle->capacity])],
                ]);
            }

            return StudentTransport::updateOrCreate(
                ['student_id' => $student->id, 'academic_year_id' => $year->id],
                [
                    'vehicle_id'  => $vehicle->id,
                    'route_id'    => $vehicle->route_id,
                    'enrolled_at' => now(),
                ]
            );
        });
    }

    public function unassign(Student $student, AcademicYear $year): void
    {
        StudentTransport::where('student_id', $student->id)
            ->where('academic_year_id', $year->id)
            ->delete();
    }
}
