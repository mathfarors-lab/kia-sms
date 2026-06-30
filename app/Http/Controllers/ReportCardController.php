<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamResult;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ReportCardController extends Controller
{
    public function show(Exam $exam, Student $student)
    {
        $this->authorizeView($exam, $student);

        $marks  = ExamMark::where(['exam_id' => $exam->id, 'student_id' => $student->id])
            ->with('subject')->get();
        $result = ExamResult::where(['exam_id' => $exam->id, 'student_id' => $student->id])->first();

        return view('report-card.show', compact('exam', 'student', 'marks', 'result'));
    }

    public function pdf(Exam $exam, Student $student)
    {
        $this->authorizeView($exam, $student);

        $marks  = ExamMark::where(['exam_id' => $exam->id, 'student_id' => $student->id])
            ->with('subject')->get();
        $result = ExamResult::where(['exam_id' => $exam->id, 'student_id' => $student->id])->first();

        $pdf = Pdf::loadView('pdf.report-card', compact('exam', 'student', 'marks', 'result'))
            ->setPaper('a4')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        $filename = "report-card-{$student->code}-{$exam->id}.pdf";
        return $pdf->download($filename);
    }

    private function authorizeView(Exam $exam, Student $student): void
    {
        abort_unless($exam->is_published, 403, __('exam.not_published'));

        $user = Auth::user();

        if ($user->hasRole(['admin', 'principal', 'teacher'])) {
            return;
        }

        // Student can only view their own
        if ($user->hasRole('student')) {
            $own = Student::where('user_id', $user->id)->value('id');
            abort_unless($own === $student->id, 403);
            return;
        }

        // Parent can view their children
        if ($user->hasRole('parent')) {
            $childIds = $user->wards()->pluck('students.id');
            abort_unless($childIds->contains($student->id), 403);
            return;
        }

        abort(403);
    }
}
