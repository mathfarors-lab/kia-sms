<?php

namespace App\Http\Controllers;

use App\Exports\ExamMarksExport;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExamMarkController extends Controller
{
    public function index()
    {
        $this->authorize('marks.view');
        $exams = Exam::with('academicYear')->latest()->get();
        return view('exam-marks.index', compact('exams'));
    }

    public function grid(Exam $exam, Section $section)
    {
        $this->authorize('marks.entry');

        [$subjects, $students, $marks] = $this->gridData($exam, $section);

        return view('exam-marks.grid', compact('exam', 'section', 'subjects', 'students', 'marks'));
    }

    public function exportExcel(Exam $exam, Section $section)
    {
        $this->authorize('marks.entry');

        [$subjects, $students, $marks] = $this->gridData($exam, $section);

        return Excel::download(
            new ExamMarksExport($students, $subjects, $marks),
            'exam-marks-' . $exam->id . '-' . $section->id . '.xlsx'
        );
    }

    public function exportPdf(Exam $exam, Section $section)
    {
        $this->authorize('marks.entry');

        [$subjects, $students, $marks] = $this->gridData($exam, $section);

        $pdf = Pdf::loadView('pdf.exam-marks-grid', compact('exam', 'section', 'subjects', 'students', 'marks'))
            ->setPaper('a4', 'landscape')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download('exam-marks-' . $exam->id . '-' . $section->id . '.pdf');
    }

    private function gridData(Exam $exam, Section $section): array
    {
        $subjects = $section->schoolClass->subjects()->orderBy('name_en')->get();
        $students = $section->students()->orderBy('name_en')->get();

        // Load existing marks keyed by [student_id][subject_id]
        $marks = ExamMark::where('exam_id', $exam->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->keyBy('subject_id'));

        return [$subjects, $students, $marks];
    }

    public function save(Request $request, Exam $exam, Section $section)
    {
        $this->authorize('marks.entry');

        if ($exam->is_published) {
            return back()->withErrors(['exam' => __('exam.locked_published')]);
        }

        $request->validate([
            'marks'                => 'required|array',
            'marks.*.*'            => 'nullable|numeric|min:0|max:100',
        ]);

        foreach ($request->marks as $studentId => $subjectScores) {
            foreach ($subjectScores as $subjectId => $score) {
                if ($score === null || $score === '') {
                    ExamMark::where([
                        'exam_id' => $exam->id, 'student_id' => $studentId, 'subject_id' => $subjectId,
                    ])->delete();
                    continue;
                }

                ExamMark::updateOrCreate(
                    ['exam_id' => $exam->id, 'student_id' => $studentId, 'subject_id' => $subjectId],
                    ['score' => $score, 'grade' => null]
                );
            }
        }

        return back()->with('success', __('messages.saved'));
    }
}
