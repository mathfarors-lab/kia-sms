<?php

namespace App\Services;

use App\Jobs\SendAbsenceAlerts;
use App\Models\Attendance;
use App\Models\Section;
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
}
