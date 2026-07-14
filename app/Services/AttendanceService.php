<?php

namespace App\Services;

use App\Jobs\SendAbsenceAlerts;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;

class AttendanceService
{
    /**
     * Mark attendance for all students in a section.
     *
     * @param  Section  $section
     * @param  array    $rows  [['student_id' => X, 'status' => '...', 'remark' => '...'], ...]
     * @param  User     $markedBy
     * @return array{saved: int, absent_ids: int[]}
     */
    public function markSection(Section $section, array $rows, User $markedBy): array
    {
        $today     = Carbon::today()->format('Y-m-d');
        $absentIds = [];

        foreach ($rows as $row) {
            // Use whereDate() to avoid Eloquent's date cast mangling the WHERE clause in SQLite
            $attendance = Attendance::where('student_id', $row['student_id'])
                ->whereDate('date', $today)
                ->first();

            $data = [
                'section_id' => $section->id,
                'status'     => $row['status'] ?? 'present',
                'remark'     => $row['remark'] ?? null,
                'marked_by'  => $markedBy->id,
            ];

            if ($attendance) {
                $attendance->fill($data)->save();
            } else {
                Attendance::create(array_merge(['student_id' => $row['student_id'], 'date' => $today], $data));
            }

            if (($row['status'] ?? 'present') === 'absent') {
                $absentIds[] = $row['student_id'];
            }
        }

        if (!empty($absentIds)) {
            SendAbsenceAlerts::dispatch($section->id, $absentIds);
        }

        return [
            'saved'      => count($rows),
            'absent_ids' => $absentIds,
        ];
    }

    /**
     * A gate scan IS an attendance mark — same `attendances` row a teacher's
     * manual marking would produce, not a parallel record. Only called for
     * a student's FIRST scan of the day (GateScanService decides that);
     * returns null if the student has no current section to attribute the
     * mark to (nothing sensible to record).
     */
    public function markArrivalViaGateScan(Student $student, bool $isLate, Carbon $time): ?Attendance
    {
        $section = $this->currentSection($student);
        if (!$section) {
            return null;
        }

        return Attendance::create([
            'student_id'   => $student->id,
            'section_id'   => $section->id,
            'date'         => $time->toDateString(),
            'status'       => $isLate ? 'late' : 'present',
            'method'       => 'gate_scan',
            'arrival_time' => $time->toTimeString(),
        ]);
    }

    /** Second gate scan of the day (departure tracking enabled) updates the same row. */
    public function markDepartureViaGateScan(Attendance $attendance, Carbon $time): void
    {
        $attendance->update(['departure_time' => $time->toTimeString()]);
    }

    /** Today's existing attendance row for a student, if any — gate scan and manual marking share one row. */
    public function todaysAttendance(Student $student, Carbon $date): ?Attendance
    {
        return Attendance::where('student_id', $student->id)
            ->whereDate('date', $date->toDateString())
            ->first();
    }

    private function currentSection(Student $student): ?Section
    {
        $year = AcademicYear::where('is_active', true)->first();
        if (!$year) {
            return null;
        }

        return $student->sections()->wherePivot('academic_year_id', $year->id)->first();
    }
}
