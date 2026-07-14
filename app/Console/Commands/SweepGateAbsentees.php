<?php

namespace App\Console\Commands;

use App\Jobs\SendAbsenceAlerts;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Section;
use App\Models\Setting;
use App\Support\BranchContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Runs every 15 minutes (routes/console.php); for each active branch whose
 * gate_absent_cutoff has just passed, marks every enrolled student with no
 * attendance record yet today as absent and dispatches the same
 * SendAbsenceAlerts job manual marking already uses.
 *
 * Idempotent by construction, not by a run-tracking flag: once a student
 * has a row for today (from this sweep or from a gate scan or a teacher's
 * manual marking), later runs simply no longer see them as unmarked. Safe
 * to run as often as the schedule likes.
 */
class SweepGateAbsentees extends Command
{
    private const DEFAULT_ABSENT_CUTOFF = '09:00';

    protected $signature = 'attendance:sweep-gate-absentees';
    protected $description = 'Mark students with no attendance record yet today as absent, once each branch\'s cutoff has passed';

    public function handle(): int
    {
        $year = AcademicYear::where('is_active', true)->first();
        if (!$year) {
            $this->info('No active academic year — nothing to sweep.');
            return self::SUCCESS;
        }

        $now = Carbon::now();
        $swept = 0;

        foreach (Branch::where('is_active', true)->get() as $branch) {
            BranchContext::within($branch->id, function () use ($branch, $year, $now, &$swept) {
                $cutoff = Setting::get('gate_absent_cutoff', self::DEFAULT_ABSENT_CUTOFF);
                $cutoffToday = $now->copy()->setTimeFromTimeString($cutoff);

                if ($now->lt($cutoffToday)) {
                    return; // this branch's cutoff hasn't arrived yet
                }

                $sections = Section::with('students')->get();

                foreach ($sections as $section) {
                    $studentIds = $section->students()
                        ->wherePivot('academic_year_id', $year->id)
                        ->pluck('students.id');

                    $alreadyMarked = Attendance::whereIn('student_id', $studentIds)
                        ->whereDate('date', $now->toDateString())
                        ->pluck('student_id');

                    $unmarkedIds = $studentIds->diff($alreadyMarked)->values();
                    if ($unmarkedIds->isEmpty()) {
                        continue;
                    }

                    foreach ($unmarkedIds as $studentId) {
                        Attendance::create([
                            'student_id' => $studentId,
                            'section_id' => $section->id,
                            'date'       => $now->toDateString(),
                            'status'     => 'absent',
                            'method'     => 'manual', // no gate scan happened — this IS the absence of one
                        ]);
                    }

                    SendAbsenceAlerts::dispatch($section->id, $unmarkedIds->all());
                    $swept += $unmarkedIds->count();
                }
            });
        }

        $this->info("Swept {$swept} student(s) into absent across active branches.");
        return self::SUCCESS;
    }
}
