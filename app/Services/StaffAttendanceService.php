<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\StaffAttendance;
use Illuminate\Support\Carbon;

/**
 * Staff attendance's only writer today is the gate scan station — mirrors
 * AttendanceService's student-side gate-scan methods exactly.
 */
class StaffAttendanceService
{
    public function markArrivalViaGateScan(Staff $staff, bool $isLate, Carbon $time): StaffAttendance
    {
        return StaffAttendance::create([
            'staff_id'     => $staff->id,
            'date'         => $time->toDateString(),
            'status'       => $isLate ? 'late' : 'present',
            'method'       => 'gate_scan',
            'arrival_time' => $time->toTimeString(),
        ]);
    }

    public function markDepartureViaGateScan(StaffAttendance $attendance, Carbon $time): void
    {
        $attendance->update(['departure_time' => $time->toTimeString()]);
    }

    public function todaysAttendance(Staff $staff, Carbon $date): ?StaffAttendance
    {
        return StaffAttendance::where('staff_id', $staff->id)
            ->whereDate('date', $date->toDateString())
            ->first();
    }
}
