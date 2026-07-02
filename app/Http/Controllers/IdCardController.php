<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Services\DocumentService;
use App\Support\Permissions as P;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class IdCardController extends Controller
{
    public function __construct(private DocumentService $docs) {}

    // ── Student ID card ──────────────────────────────────────────────────────────

    /** HTML preview — used for testing and browser preview. */
    public function showStudent(Student $student)
    {
        $this->authorizeStudent($student);

        $year    = AcademicYear::where('is_active', true)->first();
        $secQ    = $student->sections()->with('schoolClass');
        if ($year) {
            $secQ = $secQ->wherePivot('academic_year_id', $year->id);
        }
        $section = $secQ->first();

        $photoUri = $this->docs->photoDataUri($student->photo);
        $qrUri    = $this->docs->qrDataUri($student->student_code);

        return view('id-cards.student', compact('student', 'section', 'year', 'photoUri', 'qrUri'));
    }

    /** Single student CR80 PDF. */
    public function pdfStudent(Student $student)
    {
        $this->authorizeStudent($student);

        $year    = AcademicYear::where('is_active', true)->first();
        $secQ    = $student->sections()->with('schoolClass');
        if ($year) {
            $secQ = $secQ->wherePivot('academic_year_id', $year->id);
        }
        $section = $secQ->first();

        $photoUri = $this->docs->photoDataUri($student->photo);
        $qrUri    = $this->docs->qrDataUri($student->student_code);

        // CR80: 85.6 × 54 mm in points (1pt = 1/72 inch, 1mm = 72/25.4 pt)
        $pdf = Pdf::loadView('pdf.id-card-student', compact('student', 'section', 'year', 'photoUri', 'qrUri'))
            ->setPaper([0, 0, 242.55, 153.07])
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false, 'dpi' => 150]);

        return $pdf->download("id-card-{$student->student_code}.pdf");
    }

    // ── Staff ID card ────────────────────────────────────────────────────────────

    public function pdfStaff(Staff $staff)
    {
        $this->authorize(P::ID_CARDS_GENERATE);

        $staff->load('user');
        $photoUri = $this->docs->photoDataUri($staff->photo);
        $qrUri    = $this->docs->qrDataUri($staff->staff_code);

        $pdf = Pdf::loadView('pdf.id-card-staff', compact('staff', 'photoUri', 'qrUri'))
            ->setPaper([0, 0, 242.55, 153.07])
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false, 'dpi' => 150]);

        return $pdf->download("id-card-staff-{$staff->staff_code}.pdf");
    }

    // ── Batch: entire section in one PDF ─────────────────────────────────────────

    /** HTML preview for batch — testable. */
    public function batchPreview(Section $section)
    {
        $this->authorize(P::ID_CARDS_GENERATE);

        $year  = AcademicYear::where('is_active', true)->first();
        $stuQ  = $section->students();
        if ($year) {
            $stuQ = $stuQ->wherePivot('academic_year_id', $year->id);
        }
        $students = $stuQ->get();

        $section->load('schoolClass');

        $cards = $students->map(fn ($s) => [
            'student'  => $s,
            'section'  => $section,
            'year'     => $year,
            'photoUri' => $this->docs->photoDataUri($s->photo),
            'qrUri'    => $this->docs->qrDataUri($s->student_code),
        ]);

        return view('id-cards.batch', compact('section', 'year', 'cards'));
    }

    /** Batch PDF — all students in a section, multiple cards per A4 page. */
    public function batchPdf(Section $section)
    {
        $this->authorize(P::ID_CARDS_GENERATE);

        $year  = AcademicYear::where('is_active', true)->first();
        $stuQ  = $section->students();
        if ($year) {
            $stuQ = $stuQ->wherePivot('academic_year_id', $year->id);
        }
        $students = $stuQ->get();

        $section->load('schoolClass');

        $cards = $students->map(fn ($s) => [
            'student'  => $s,
            'section'  => $section,
            'year'     => $year,
            'photoUri' => $this->docs->photoDataUri($s->photo),
            'qrUri'    => $this->docs->qrDataUri($s->student_code),
        ]);

        $pdf = Pdf::loadView('pdf.id-card-batch', compact('section', 'year', 'cards'))
            ->setPaper('a4')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false, 'dpi' => 150]);

        $name = $section->schoolClass->name ?? 'section';
        return $pdf->download("id-cards-batch-{$name}-{$section->name}.pdf");
    }

    // ── Authorization ────────────────────────────────────────────────────────────

    private function authorizeStudent(Student $student): void
    {
        $user = Auth::user();

        if ($user->hasRole(['admin', 'principal', 'teacher', 'receptionist'])) {
            return;
        }

        $this->authorize(P::ID_CARDS_GENERATE); // students/parents have this via role or not

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
