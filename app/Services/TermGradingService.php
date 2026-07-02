<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\TermResult;
use Illuminate\Support\Facades\DB;

class TermGradingService
{
    public function __construct(private GradingService $grading) {}

    /**
     * Compute (or recompute) term results for a given year + semester.
     * semester = null → annual (combines S1 + S2 term results).
     * Idempotent — safe to re-run unless finalized.
     * Returns true on success, false if locked (finalized).
     */
    public function compute(AcademicYear $year, ?int $semester): bool
    {
        if ($semester === null) {
            return $this->computeAnnual($year);
        }
        return $this->computeSemester($year, $semester);
    }

    private function computeSemester(AcademicYear $year, int $semester): bool
    {
        // Locked when any result for this period is finalized
        if (TermResult::where('academic_year_id', $year->id)
                ->where('semester', $semester)
                ->where('is_finalized', true)
                ->exists()) {
            return false;
        }

        $policy   = Setting::get('missing_mark_policy', 'exclude-and-flag');
        $passmark = (float) Setting::get('pass_mark', 50);

        $exams = Exam::published()
            ->where('academic_year_id', $year->id)
            ->where('semester', $semester)
            ->get();

        if ($exams->isEmpty()) {
            return true;
        }

        $sectionIds = DB::table('student_section')
            ->where('academic_year_id', $year->id)
            ->distinct()
            ->pluck('section_id');

        $sections = Section::whereIn('id', $sectionIds)->get();

        foreach ($sections as $section) {
            $students = $section->students()->wherePivot('academic_year_id', $year->id)->get();
            if ($students->isEmpty()) {
                continue;
            }

            $sectionStudentIds = $students->pluck('id');

            // Pre-build (exam_id → [subject_id, ...]) map for missing-as-zero policy
            $examSubjectMap = [];
            if ($policy === 'treat-missing-as-zero') {
                foreach ($exams as $exam) {
                    $examSubjectMap[$exam->id] = ExamMark::where('exam_id', $exam->id)
                        ->whereIn('student_id', $sectionStudentIds)
                        ->distinct()
                        ->pluck('subject_id')
                        ->all();
                }
            }

            $allResults = [];

            foreach ($students as $student) {
                $wScoreSum  = 0.0;
                $wGpaSum    = 0.0;
                $wCoefSum   = 0.0;
                $hasMissing = false;

                foreach ($exams as $exam) {
                    $examWeight = (float) $exam->weight;
                    $marks = ExamMark::where('exam_id', $exam->id)
                        ->where('student_id', $student->id)
                        ->with('subject')
                        ->get();

                    if ($marks->isEmpty()) {
                        $hasMissing = true;
                        if ($policy === 'treat-missing-as-zero') {
                            $subjectIds = $examSubjectMap[$exam->id] ?? [];
                            foreach (Subject::whereIn('id', $subjectIds)->get() as $subject) {
                                $coef = (float) ($subject->coefficient ?? 1);
                                $wCoefSum += $examWeight * $coef;
                                // score=0, gpa=0 → numerators stay unchanged
                            }
                        }
                        continue;
                    }

                    foreach ($marks as $mark) {
                        $coef  = (float) ($mark->subject->coefficient ?? 1);
                        $grade = $this->grading->gradeFor((float) $mark->score);
                        $gpa   = $grade ? (float) $grade->gpa : 0.0;

                        $wScoreSum += (float) $mark->score * $examWeight * $coef;
                        $wGpaSum   += $gpa * $examWeight * $coef;
                        $wCoefSum  += $examWeight * $coef;
                    }
                }

                $average = $wCoefSum > 0 ? round($wScoreSum / $wCoefSum, 2) : 0.0;
                $gpa     = $wCoefSum > 0 ? round($wGpaSum   / $wCoefSum, 2) : 0.0;
                $total   = round($wScoreSum, 2);
                $result  = $average >= $passmark ? 'pass' : 'fail';

                $allResults[$student->id] = [
                    'academic_year_id'  => $year->id,
                    'semester'          => $semester,
                    'student_id'        => $student->id,
                    'section_id'        => $section->id,
                    'total'             => $total,
                    'average'           => $average,
                    'gpa'               => $gpa,
                    'result'            => $result,
                    'has_missing_marks' => $hasMissing,
                    'rank'              => null,
                ];
            }

            $allResults = $this->assignRanks($allResults);

            foreach ($allResults as $studentId => $data) {
                TermResult::updateOrCreate(
                    ['academic_year_id' => $year->id, 'semester' => $semester, 'student_id' => $studentId],
                    $data
                );
            }
        }

        return true;
    }

    private function computeAnnual(AcademicYear $year): bool
    {
        if (TermResult::where('academic_year_id', $year->id)
                ->whereNull('semester')
                ->where('is_finalized', true)
                ->exists()) {
            return false;
        }

        $passmark = (float) Setting::get('pass_mark', 50);

        $sem1 = TermResult::where('academic_year_id', $year->id)->where('semester', 1)->get()->keyBy('student_id');
        $sem2 = TermResult::where('academic_year_id', $year->id)->where('semester', 2)->get()->keyBy('student_id');

        $allStudentIds = $sem1->keys()->merge($sem2->keys())->unique();
        if ($allStudentIds->isEmpty()) {
            return true;
        }

        $sectionIds = DB::table('student_section')
            ->where('academic_year_id', $year->id)
            ->distinct()
            ->pluck('section_id');

        $sections = Section::whereIn('id', $sectionIds)->get();

        foreach ($sections as $section) {
            $students = $section->students()->wherePivot('academic_year_id', $year->id)->get();
            $sectionStudentIds = $students->pluck('id');
            $inScope = $allStudentIds->intersect($sectionStudentIds);
            if ($inScope->isEmpty()) {
                continue;
            }

            $allResults = [];

            foreach ($inScope as $studentId) {
                $s1 = $sem1->get($studentId);
                $s2 = $sem2->get($studentId);

                $count = ($s1 ? 1 : 0) + ($s2 ? 1 : 0);
                if ($count === 0) {
                    continue;
                }

                $average = round((($s1?->average ?? 0) + ($s2?->average ?? 0)) / $count, 2);
                $gpa     = round((($s1?->gpa ?? 0) + ($s2?->gpa ?? 0)) / $count, 2);
                $total   = round(($s1?->total ?? 0) + ($s2?->total ?? 0), 2);
                $result  = $average >= $passmark ? 'pass' : 'fail';

                $allResults[$studentId] = [
                    'academic_year_id'  => $year->id,
                    'semester'          => null,
                    'student_id'        => $studentId,
                    'section_id'        => $section->id,
                    'total'             => $total,
                    'average'           => $average,
                    'gpa'               => $gpa,
                    'result'            => $result,
                    'has_missing_marks' => ($s1?->has_missing_marks || $s2?->has_missing_marks),
                    'rank'              => null,
                ];
            }

            $allResults = $this->assignRanks($allResults);

            foreach ($allResults as $studentId => $data) {
                TermResult::updateOrCreate(
                    ['academic_year_id' => $year->id, 'semester' => null, 'student_id' => $studentId],
                    $data
                );
            }
        }

        return true;
    }

    /** Standard competition ranking: ties share a rank. */
    private function assignRanks(array $results): array
    {
        $sorted = collect($results)->sortByDesc('average')->values();

        $currentRank = 1;
        for ($i = 0; $i < $sorted->count(); $i++) {
            $sid = $sorted[$i]['student_id'];
            if ($i > 0 && $results[$sid]['average'] === $results[$sorted[$i - 1]['student_id']]['average']) {
                $results[$sid]['rank'] = $results[$sorted[$i - 1]['student_id']]['rank'];
            } else {
                $results[$sid]['rank'] = $currentRank;
            }
            $currentRank++;
        }

        return $results;
    }
}
