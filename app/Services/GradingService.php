<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamResult;
use App\Models\GradeScale;
use App\Models\Setting;

class GradingService
{
    public function gradeFor(float $score): ?GradeScale
    {
        return GradeScale::where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderByDesc('min_score')
            ->first();
    }

    public function computeResults(Exam $exam): void
    {
        $passmark = (float) Setting::get('pass_mark', 50);

        $studentIds = ExamMark::where('exam_id', $exam->id)
            ->distinct()
            ->pluck('student_id');

        $results = [];

        foreach ($studentIds as $studentId) {
            $marks = ExamMark::where('exam_id', $exam->id)
                ->where('student_id', $studentId)
                ->with('subject')
                ->get();

            $weightedScoreSum = 0.0;
            $weightedGpaSum   = 0.0;
            $coefficientSum   = 0.0;

            foreach ($marks as $mark) {
                $coef  = (float) ($mark->subject->coefficient ?? 1.00);
                $grade = $this->gradeFor((float) $mark->score);
                $gpa   = $grade ? (float) $grade->gpa : 0.0;

                $weightedScoreSum += (float) $mark->score * $coef;
                $weightedGpaSum   += $gpa * $coef;
                $coefficientSum   += $coef;

                $mark->update(['grade' => $grade?->grade ?? 'F']);
            }

            $average = $coefficientSum > 0 ? round($weightedScoreSum / $coefficientSum, 2) : 0.0;
            $gpa     = $coefficientSum > 0 ? round($weightedGpaSum   / $coefficientSum, 2) : 0.0;
            $total   = round($weightedScoreSum, 2);
            $result  = $average >= $passmark ? 'pass' : 'fail';

            $results[$studentId] = [
                'exam_id'    => $exam->id,
                'student_id' => $studentId,
                'total'      => $total,
                'average'    => $average,
                'gpa'        => $gpa,
                'result'     => $result,
                'rank'       => null,
            ];
        }

        // Standard competition ranking (ties share rank)
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

        foreach ($results as $data) {
            ExamResult::updateOrCreate(
                ['exam_id' => $data['exam_id'], 'student_id' => $data['student_id']],
                $data
            );
        }
    }
}
