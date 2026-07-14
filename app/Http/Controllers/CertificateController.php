<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\IssuedDocument;
use App\Models\Student;
use App\Services\DocumentIssuanceService;
use App\Support\Permissions as P;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    public function __construct(private DocumentIssuanceService $issuance) {}

    // ── Enrollment Confirmation ──────────────────────────────────────────────────

    public function enrollment(Student $student)
    {
        $this->authorize(P::CERTIFICATES_ISSUE);
        abort_unless($student->status === 'enrolled', 422, __('documents.cert_requires_enrolled'));

        $year   = AcademicYear::where('is_active', true)->first();
        $certNo = $this->issuance->issueForStudent($student, IssuedDocument::TYPE_ENROLLMENT_CERT)->number;

        return view('certificates.enrollment', compact('student', 'year', 'certNo'));
    }

    public function enrollmentPdf(Student $student)
    {
        $this->authorize(P::CERTIFICATES_ISSUE);
        abort_unless($student->status === 'enrolled', 422, __('documents.cert_requires_enrolled'));

        $year   = AcademicYear::where('is_active', true)->first();
        $certNo = $this->issuance->issueForStudent($student, IssuedDocument::TYPE_ENROLLMENT_CERT)->number;

        $pdf = Pdf::loadView('pdf.certificate-enrollment', compact('student', 'year', 'certNo'))
            ->setPaper('a4')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download("enrollment-{$student->student_code}.pdf");
    }

    // ── Leaving / Transfer Certificate ──────────────────────────────────────────

    public function leaving(Student $student)
    {
        $this->authorize(P::CERTIFICATES_ISSUE);

        $year   = AcademicYear::where('is_active', true)->first();
        $certNo = $this->issuance->issueForStudent($student, IssuedDocument::TYPE_LEAVING_CERT)->number;

        return view('certificates.leaving', compact('student', 'year', 'certNo'));
    }

    public function leavingPdf(Student $student)
    {
        $this->authorize(P::CERTIFICATES_ISSUE);

        $year   = AcademicYear::where('is_active', true)->first();
        $certNo = $this->issuance->issueForStudent($student, IssuedDocument::TYPE_LEAVING_CERT)->number;

        $pdf = Pdf::loadView('pdf.certificate-leaving', compact('student', 'year', 'certNo'))
            ->setPaper('a4')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download("leaving-cert-{$student->student_code}.pdf");
    }

    // ── Graduation Certificate ────────────────────────────────────────────────────

    public function graduation(Student $student)
    {
        $this->authorize(P::CERTIFICATES_ISSUE);
        abort_unless($student->status === 'graduated', 403, __('documents.cert_requires_graduated'));

        $year   = AcademicYear::where('is_active', true)->first();
        $certNo = $this->issuance->issueForStudent($student, IssuedDocument::TYPE_GRADUATION_CERT)->number;

        return view('certificates.graduation', compact('student', 'year', 'certNo'));
    }

    public function graduationPdf(Student $student)
    {
        $this->authorize(P::CERTIFICATES_ISSUE);
        abort_unless($student->status === 'graduated', 403, __('documents.cert_requires_graduated'));

        $year   = AcademicYear::where('is_active', true)->first();
        $certNo = $this->issuance->issueForStudent($student, IssuedDocument::TYPE_GRADUATION_CERT)->number;

        $pdf = Pdf::loadView('pdf.certificate-graduation', compact('student', 'year', 'certNo'))
            ->setPaper('a4')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download("graduation-{$student->student_code}.pdf");
    }
}
