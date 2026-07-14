<?php

namespace App\Services;

use App\Models\GateScanLog;
use App\Models\Scopes\BranchScope;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Notifications\StudentGateEvent;
use App\Support\BranchContext;
use Illuminate\Support\Carbon;

/**
 * Orchestrates a single gate scan: resolve the code, enforce branch
 * isolation, debounce, decide arrival vs. departure, delegate the actual
 * attendance write to AttendanceService/StaffAttendanceService (never
 * duplicates their logic), log the raw event, dispatch the family alert.
 *
 * The lookup step deliberately bypasses BranchScope: querying scoped would
 * make a foreign-branch card indistinguishable from a code that doesn't
 * exist at all (both come back "not found"), which is exactly the "wrong
 * campus" vs. "not recognized" distinction the gate station needs to show.
 * Every subsequent read/write (GateScanLog, Attendance) stays scoped as
 * normal — only this one lookup is deliberately unscoped, same reasoning
 * as AnalyticsService::perBranchOverview() in M2.
 */
class GateScanService
{
    private const DEBOUNCE_SECONDS = 120;
    private const DEFAULT_LATE_CUTOFF = '07:30';

    public function __construct(
        private AttendanceService $attendance,
        private StaffAttendanceService $staffAttendance,
    ) {}

    /** @return array{result: string, type: ?string, event: ?string, entity: Student|Staff|null} */
    public function scan(string $code, User $operator): array
    {
        $code = trim($code);
        $gateBranchId = BranchContext::current();
        $now = Carbon::now();

        $student = Student::withoutGlobalScope(BranchScope::class)->where('student_code', $code)->first();
        $staff = $student ? null : Staff::withoutGlobalScope(BranchScope::class)->where('staff_code', $code)->first();

        $entity = $student ?? $staff;
        $type = $student ? 'student' : ($staff ? 'staff' : null);

        if (!$entity) {
            $this->log($code, null, null, 'unmatched', null, $operator, $now);
            return ['result' => 'unmatched', 'type' => null, 'event' => null, 'entity' => null];
        }

        if ($entity->branch_id !== $gateBranchId) {
            $this->log($code, $student?->id, $staff?->id, 'wrong_branch', null, $operator, $now);
            return ['result' => 'wrong_branch', 'type' => $type, 'event' => null, 'entity' => $entity];
        }

        if ($this->isDebounced($student?->id, $staff?->id, $now)) {
            $this->log($code, $student?->id, $staff?->id, 'duplicate', null, $operator, $now);
            return ['result' => 'duplicate', 'type' => $type, 'event' => null, 'entity' => $entity];
        }

        $event = $type === 'student'
            ? $this->processStudent($student, $now)
            : $this->processStaff($staff, $now);

        $this->log($code, $student?->id, $staff?->id, $event ? 'matched' : 'duplicate', $event, $operator, $now);

        return ['result' => $event ? 'matched' : 'duplicate', 'type' => $type, 'event' => $event, 'entity' => $entity];
    }

    private function processStudent(Student $student, Carbon $now): ?string
    {
        $existing = $this->attendance->todaysAttendance($student, $now);

        if (!$existing) {
            $marked = $this->attendance->markArrivalViaGateScan($student, $this->isLate($now), $now);
            if (!$marked) {
                return null; // no current section to attribute the mark to
            }
            $this->notifyGuardian($student, 'arrival', $now);
            return 'arrival';
        }

        if ($this->tracksDeparture() && !$existing->departure_time) {
            $this->attendance->markDepartureViaGateScan($existing, $now);
            $this->notifyGuardian($student, 'departure', $now);
            return 'departure';
        }

        return null; // already fully recorded today — no new action
    }

    private function processStaff(Staff $staff, Carbon $now): ?string
    {
        $existing = $this->staffAttendance->todaysAttendance($staff, $now);

        if (!$existing) {
            $this->staffAttendance->markArrivalViaGateScan($staff, $this->isLate($now), $now);
            return 'arrival';
        }

        if ($this->tracksDeparture() && !$existing->departure_time) {
            $this->staffAttendance->markDepartureViaGateScan($existing, $now);
            return 'departure';
        }

        return null;
    }

    private function notifyGuardian(Student $student, string $eventType, Carbon $now): void
    {
        $student->loadMissing('guardians');
        $guardian = $student->guardians->firstWhere('pivot.is_primary', true) ?? $student->guardians->first();

        $guardian?->notify(new StudentGateEvent($student, $eventType, $now));
    }

    private function isDebounced(?int $studentId, ?int $staffId, Carbon $now): bool
    {
        return GateScanLog::where('result', 'matched')
            ->where('scanned_at', '>=', $now->copy()->subSeconds(self::DEBOUNCE_SECONDS))
            ->when($studentId, fn ($q) => $q->where('student_id', $studentId))
            ->when($staffId, fn ($q) => $q->where('staff_id', $staffId))
            ->exists();
    }

    private function isLate(Carbon $now): bool
    {
        $cutoff = Setting::get('gate_late_cutoff', self::DEFAULT_LATE_CUTOFF);
        $cutoffToday = $now->copy()->setTimeFromTimeString($cutoff);

        return $now->gt($cutoffToday);
    }

    private function tracksDeparture(): bool
    {
        $value = strtolower(trim((string) Setting::get('gate_track_departure', '0')));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function log(string $code, ?int $studentId, ?int $staffId, string $result, ?string $event, User $operator, Carbon $now): void
    {
        GateScanLog::create([
            'scanned_code' => $code,
            'student_id'   => $studentId,
            'staff_id'     => $staffId,
            'result'       => $result,
            'event'        => $event,
            'scanned_by'   => $operator->id,
            'scanned_at'   => $now,
        ]);
    }
}
