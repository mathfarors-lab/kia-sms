<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Student;
use App\Models\TermResult;
use App\Support\Permissions as P;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class TranscriptController extends Controller
{
    /** HTML preview — testable and browseable. */
    public function show(Student $student)
    {
        $this->authorizeView($student);

        $data = $this->buildTranscriptData($student);

        return view('transcripts.show', array_merge(['student' => $student], $data));
    }

    /** PDF download. */
    public function pdf(Student $student)
    {
        $this->authorizeView($student);

        $data = $this->buildTranscriptData($student);

        $pdf = Pdf::loadView('pdf.transcript', array_merge(['student' => $student], $data))
            ->setPaper('a4')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download("transcript-{$student->student_code}.pdf");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private function buildTranscriptData(Student $student): array
    {
        // Only show years where the student has at least one published term result
        $yearIds = TermResult::where('student_id', $student->id)
            ->where('is_published', true)
            ->distinct()
            ->pluck('academic_year_id');

        $years = AcademicYear::whereIn('id', $yearIds)
            ->orderBy('start_date')
            ->get();

        $yearBlocks = $years->map(function (AcademicYear $year) use ($student) {
            // Term results for this year (published only)
            $termResults = TermResult::where('academic_year_id', $year->id)
                ->where('student_id', $student->id)
                ->where('is_published', true)
                ->get()
                ->keyBy(fn ($tr) => $tr->semester ?? 'annual');

            // Per-subject averages per semester (from published exam marks)
            $subjectsBySem = [];
            foreach ([1, 2] as $sem) {
                $examIds = Exam::published()
                    ->where('academic_year_id', $year->id)
                    ->where('semester', $sem)
                    ->pluck('id');

                if ($examIds->isNotEmpty()) {
                    $subjectsBySem[$sem] = ExamMark::whereIn('exam_id', $examIds)
                        ->where('student_id', $student->id)
                        ->with('subject')
                        ->get()
                        ->groupBy('subject_id')
                        ->map(fn ($marks) => [
                            'subject' => $marks->first()->subject,
                            'average' => round($marks->avg('score'), 1),
                            'grade'   => $marks->first()->grade,
                        ]);
                }
            }

            // Unified subject list across both semesters
            $allSubjectIds = collect($subjectsBySem)->flatMap(fn ($s) => $s->keys())->unique();

            return [
                'year'         => $year,
                'termResults'  => $termResults,
                'subjectsBySem' => $subjectsBySem,
                'allSubjectIds' => $allSubjectIds,
            ];
        });

        return ['yearBlocks' => $yearBlocks];
    }

    private function authorizeView(Student $student): void
    {
        $user = Auth::user();

        if ($user->hasRole(['admin', 'principal'])) {
            return;
        }

        if ($user->hasRole('teacher')) {
            abort_unless($user->staff && $user->staff->canAccessStudent($student), 403);
            return;
        }

        $this->authorize(P::TRANSCRIPTS_VIEW);

        if ($user->hasRole('student')) {
            $own = Student::where('user_id', $user->id)->value('id');
            abort_unless($own === $student->id, 403);
            return;
        }

        if ($user->hasRole('parent')) {
            abort_unless($user->wards()->where('students.id', $student->id)->exists(), 403);
            return;
        }

        abort(403);
    }
}
