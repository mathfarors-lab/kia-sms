<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\IssuedDocument;
use App\Models\Section;
use App\Models\Student;
use App\Models\TermResult;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    public function __construct(private DocumentIssuanceService $documents) {}

    /**
     * Build the dry-run preview — no DB writes.
     *
     * Returns:
     *   promote  => [{student, from_section, to_section, reason}]
     *   graduate => [{student, from_section, reason}]
     *   retain   => [{student, from_section, reason}]
     *   withdraw => [{student, from_section, reason}]
     *   skipped  => [{student, from_section}]   (already enrolled in toYear)
     *   errors   => [{student, from_section, reason}]  (next class has no sections)
     *
     * @param array<int,string> $overrides  student_id => 'promote'|'retain'|'graduate'|'withdraw'
     */
    public function preview(AcademicYear $fromYear, AcademicYear $toYear, array $overrides = []): array
    {
        $results = [
            'promote'  => [],
            'graduate' => [],
            'retain'   => [],
            'withdraw' => [],
            'skipped'  => [],
            'errors'   => [],
        ];

        // Students already enrolled in toYear — skip unconditionally
        $alreadyIn = DB::table('student_section')
            ->where('academic_year_id', $toYear->id)
            ->pluck('student_id')
            ->flip();

        // All enrollments in fromYear: student_id => pivot row
        $fromRows = DB::table('student_section')
            ->where('academic_year_id', $fromYear->id)
            ->get()
            ->keyBy('student_id');

        if ($fromRows->isEmpty()) {
            return $results;
        }

        // Load students
        $students = Student::whereIn('id', $fromRows->keys())->get()->keyBy('id');

        // Load sections with their class → nextClass → nextClass's sections
        $sectionIds = $fromRows->pluck('section_id')->unique();
        $sections   = Section::whereIn('id', $sectionIds)
            ->with('schoolClass.nextClass.sections')
            ->get()
            ->keyBy('id');

        // Load annual TermResults (semester = null) for fromYear
        $annualResults = TermResult::where('academic_year_id', $fromYear->id)
            ->whereNull('semester')
            ->get()
            ->keyBy('student_id');

        foreach ($fromRows as $studentId => $row) {
            $student     = $students->get($studentId);
            $fromSection = $sections->get($row->section_id);

            if (!$student) {
                continue;
            }

            // Already enrolled in toYear → skip
            if (isset($alreadyIn[$studentId])) {
                $results['skipped'][] = compact('student', 'fromSection');
                continue;
            }

            $override = $overrides[$studentId] ?? null;

            // Explicit withdraw override
            if ($override === 'withdraw') {
                $results['withdraw'][] = compact('student', 'fromSection') + ['reason' => 'override'];
                continue;
            }

            // Explicit graduate override
            if ($override === 'graduate') {
                $results['graduate'][] = compact('student', 'fromSection') + ['reason' => 'override'];
                continue;
            }

            // Determine pass/fail from annual result
            $annualResult = $annualResults->get($studentId);
            $passed       = $annualResult?->result === 'pass';

            // Retain: fail, no-result, or explicit override
            if ($override === 'retain' || (!$override && !$passed)) {
                $results['retain'][] = compact('student', 'fromSection') + [
                    'reason' => match(true) {
                        $override === 'retain'    => 'override',
                        $annualResult === null     => 'no-result',
                        default                    => 'fail',
                    },
                ];
                continue;
            }

            // Promote (passed, or override='promote'): look for next class
            $nextClass = $fromSection?->schoolClass?->nextClass;

            if (!$nextClass) {
                // No next class configured → graduate
                $results['graduate'][] = compact('student', 'fromSection') + [
                    'reason' => $override === 'promote' ? 'override' : 'final-grade',
                ];
                continue;
            }

            // Find first section of the next class
            $toSection = $nextClass->sections->first();

            if (!$toSection) {
                $results['errors'][] = compact('student', 'fromSection') + [
                    'reason' => "Next class \"{$nextClass->name}\" has no sections — create one first.",
                ];
                continue;
            }

            $results['promote'][] = compact('student', 'fromSection', 'toSection') + [
                'reason' => $override === 'promote' ? 'override' : 'pass',
            ];
        }

        return $results;
    }

    /**
     * Execute the promotion inside a single DB transaction.
     * Calls preview() internally so the same logic governs both dry-run and execution.
     *
     * @return array{promoted:int, retained:int, graduated:int, withdrawn:int, skipped:int, errors:int}
     */
    public function execute(
        AcademicYear $fromYear,
        AcademicYear $toYear,
        array        $overrides = [],
        bool         $activateNewYear = false
    ): array {
        $preview = $this->preview($fromYear, $toYear, $overrides);

        $counts = [
            'promoted'  => 0,
            'retained'  => 0,
            'graduated' => 0,
            'withdrawn' => 0,
            'skipped'   => count($preview['skipped']),
            'errors'    => count($preview['errors']),
        ];

        DB::transaction(function () use ($preview, $toYear, $fromYear, $activateNewYear, &$counts) {
            $now = now();

            // Promote → new section in next class, new year
            foreach ($preview['promote'] as $item) {
                $inserted = DB::table('student_section')->insertOrIgnore([
                    'student_id'       => $item['student']->id,
                    'section_id'       => $item['toSection']->id,
                    'academic_year_id' => $toYear->id,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
                if ($inserted) {
                    $counts['promoted']++;
                } else {
                    $counts['skipped']++;
                }
            }

            // Retain → same section, new year
            foreach ($preview['retain'] as $item) {
                $inserted = DB::table('student_section')->insertOrIgnore([
                    'student_id'       => $item['student']->id,
                    'section_id'       => $item['fromSection']->id,
                    'academic_year_id' => $toYear->id,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
                if ($inserted) {
                    $counts['retained']++;
                } else {
                    $counts['skipped']++;
                }
            }

            // Graduate → update student status, no new enrollment
            foreach ($preview['graduate'] as $item) {
                $item['student']->update(['status' => 'graduated']);
                $this->documents->issueForStudent($item['student'], IssuedDocument::TYPE_GRADUATION_CERT);
                $counts['graduated']++;
            }

            // Withdraw → update student status, no new enrollment
            foreach ($preview['withdraw'] as $item) {
                $item['student']->update(['status' => 'dropped']);
                $this->documents->issueForStudent($item['student'], IssuedDocument::TYPE_LEAVING_CERT);
                $counts['withdrawn']++;
            }

            // Optionally flip the active year
            if ($activateNewYear) {
                AcademicYear::where('is_active', true)->update(['is_active' => false]);
                $toYear->update(['is_active' => true]);
            }

            // Immutable audit log — never updates, only appends
            activity('promotion')
                ->causedBy(Auth::user())
                ->withProperties(array_merge($counts, [
                    'from_year_id'      => $fromYear->id,
                    'from_year'         => $fromYear->name,
                    'to_year_id'        => $toYear->id,
                    'to_year'           => $toYear->name,
                    'activate_new_year' => $activateNewYear,
                ]))
                ->log('Year-end rollover executed');
        });

        return $counts;
    }
}
