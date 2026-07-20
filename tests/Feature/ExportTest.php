<?php

namespace Tests\Feature;

use App\Exports\AdmissionsExport;
use App\Exports\ExamMarksExport;
use App\Exports\InvoicesListExport;
use App\Exports\StaffExport;
use App\Exports\StudentsExport;
use App\Models\AcademicYear;
use App\Models\AdmissionApplication;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Every export must reflect the SAME filtered/scoped rows the on-screen
 * list shows — not the full table. Excel::fake() lets us inspect the
 * exact Collection each Export class received; PDF routes only get a
 * lightweight assertOk() smoke test, matching the rest of the suite's
 * treatment of DomPDF routes (see TranscriptTest/CertificateTest).
 */
class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        return $user;
    }

    private function makeStudent(string $nameEn, array $overrides = []): Student
    {
        return Student::create(array_merge([
            'user_id'      => User::factory()->create(['status' => 'active'])->id,
            'student_code' => 'K-' . uniqid(),
            'name_en'      => $nameEn,
            'name_km'      => $nameEn,
            'gender'       => 'female',
            'status'       => 'enrolled',
        ], $overrides));
    }

    // ── Students ─────────────────────────────────────────────────────────────

    public function test_students_export_excel_respects_search_filter(): void
    {
        Excel::fake();

        $alice = $this->makeStudent('Alice');
        $bob   = $this->makeStudent('Bob');

        $this->actingAs($this->makeUser('admin'))
            ->get(route('students.export-excel', ['search' => 'Alice']))
            ->assertOk();

        Excel::assertDownloaded('students-' . now()->format('Y-m-d') . '.xlsx', function (StudentsExport $export) use ($alice, $bob) {
            $codes = $export->collection()->pluck('student_code');
            return $codes->contains($alice->student_code) && !$codes->contains($bob->student_code);
        });
    }

    public function test_students_export_pdf_returns_ok(): void
    {
        $this->makeStudent('Alice');

        $this->actingAs($this->makeUser('admin'))
            ->get(route('students.export-pdf'))
            ->assertOk();
    }

    // ── Staff ────────────────────────────────────────────────────────────────

    public function test_staff_export_excel_respects_department_filter(): void
    {
        Excel::fake();

        $math = Staff::create(['user_id' => User::factory()->create()->id, 'staff_code' => 'ST-' . uniqid(), 'department' => 'Math']);
        $art  = Staff::create(['user_id' => User::factory()->create()->id, 'staff_code' => 'ST-' . uniqid(), 'department' => 'Art']);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('staff.export-excel', ['department' => 'Math']))
            ->assertOk();

        Excel::assertDownloaded('staff-' . now()->format('Y-m-d') . '.xlsx', function (StaffExport $export) use ($math, $art) {
            $codes = $export->collection()->pluck('staff_code');
            return $codes->contains($math->staff_code) && !$codes->contains($art->staff_code);
        });
    }

    public function test_staff_export_pdf_returns_ok(): void
    {
        Staff::create(['user_id' => User::factory()->create()->id, 'staff_code' => 'ST-' . uniqid(), 'department' => 'Math']);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('staff.export-pdf'))
            ->assertOk();
    }

    // ── Admissions ───────────────────────────────────────────────────────────

    private function makeApplication(string $status): AdmissionApplication
    {
        return AdmissionApplication::create([
            'application_no' => 'ADM-' . uniqid(),
            'name_en'        => 'Applicant-' . uniqid(),
            'name_km'        => 'បេក្ខជន',
            'gender'         => 'female',
            'status'         => $status,
        ]);
    }

    public function test_admissions_export_excel_respects_status_filter(): void
    {
        Excel::fake();

        $applied  = $this->makeApplication('applied');
        $accepted = $this->makeApplication('accepted');

        $this->actingAs($this->makeUser('admin'))
            ->get(route('admissions.export-excel', ['status' => 'accepted']))
            ->assertOk();

        Excel::assertDownloaded('admissions-' . now()->format('Y-m-d') . '.xlsx', function (AdmissionsExport $export) use ($applied, $accepted) {
            $nos = $export->collection()->pluck('application_no');
            return $nos->contains($accepted->application_no) && !$nos->contains($applied->application_no);
        });
    }

    public function test_admissions_export_pdf_returns_ok(): void
    {
        $this->makeApplication('applied');

        $this->actingAs($this->makeUser('admin'))
            ->get(route('admissions.export-pdf'))
            ->assertOk();
    }

    // ── Exam Marks (grid export) ─────────────────────────────────────────────

    private function makeGradedSection(): array
    {
        $year    = AcademicYear::create(['name' => 'Y1', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $class   = SchoolClass::create(['name' => 'Grade 10', 'level' => 'High', 'capacity' => 30]);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);
        $subject = Subject::create(['name_en' => 'Math', 'name_km' => 'M', 'code' => 'M1', 'full_mark' => 100, 'coefficient' => 1]);
        $student = $this->makeStudent('Alice');

        DB::table('student_section')->insert([
            'student_id' => $student->id, 'section_id' => $section->id,
            'academic_year_id' => $year->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('class_subject')->insert(['school_class_id' => $class->id, 'subject_id' => $subject->id]);

        $exam = Exam::create(['academic_year_id' => $year->id, 'name' => 'Midterm', 'type' => 'midterm', 'is_published' => false]);
        ExamMark::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'subject_id' => $subject->id, 'score' => 88]);

        return compact('exam', 'section', 'subject', 'student');
    }

    public function test_exam_marks_export_excel_matches_grid_scores(): void
    {
        Excel::fake();
        ['exam' => $exam, 'section' => $section, 'subject' => $subject, 'student' => $student] = $this->makeGradedSection();

        $this->actingAs($this->makeUser('admin'))
            ->get(route('exam-marks.export-excel', [$exam, $section]))
            ->assertOk();

        Excel::assertDownloaded("exam-marks-{$exam->id}-{$section->id}.xlsx", function (ExamMarksExport $export) use ($student, $subject) {
            $row = $export->map($export->collection()->firstWhere('id', $student->id));
            return $row[0] === $student->name_en && in_array('88.00', $row, true);
        });
    }

    public function test_exam_marks_export_pdf_returns_ok(): void
    {
        ['exam' => $exam, 'section' => $section] = $this->makeGradedSection();

        $this->actingAs($this->makeUser('admin'))
            ->get(route('exam-marks.export-pdf', [$exam, $section]))
            ->assertOk();
    }

    // ── Invoices ─────────────────────────────────────────────────────────────

    private function makeInvoice(Student $student, AcademicYear $year, string $status, string $number): Invoice
    {
        return Invoice::create([
            'number' => substr($number, 0, 20), 'student_id' => $student->id, 'academic_year_id' => $year->id,
            'term' => 'term_1', 'subtotal' => '50.00', 'discount' => '0.00', 'total' => '50.00',
            'paid' => $status === 'paid' ? '50.00' : '0.00', 'status' => $status,
        ]);
    }

    public function test_invoices_export_excel_respects_status_filter(): void
    {
        Excel::fake();

        $year  = AcademicYear::create(['name' => 'Y1', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $paid  = $this->makeInvoice($this->makeStudent('Alice'), $year, 'paid', 'INV-P-' . uniqid());
        $unpaid = $this->makeInvoice($this->makeStudent('Bob'), $year, 'unpaid', 'INV-U-' . uniqid());

        $this->actingAs($this->makeUser('accountant'))
            ->get(route('invoices.export-excel', ['status' => 'paid']))
            ->assertOk();

        Excel::assertDownloaded('invoices-' . now()->format('Y-m-d') . '.xlsx', function (InvoicesListExport $export) use ($paid, $unpaid) {
            $numbers = $export->collection()->pluck('number');
            return $numbers->contains($paid->number) && !$numbers->contains($unpaid->number);
        });
    }

    public function test_invoices_export_excel_scoped_to_own_children_for_parent(): void
    {
        Excel::fake();

        $year     = AcademicYear::create(['name' => 'Y1', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $ownChild = $this->makeStudent('Alice');
        $other    = $this->makeStudent('Bob');

        $parentUser = User::factory()->create(['status' => 'active']);
        $parentUser->assignRole('parent');
        DB::table('student_guardian')->insert([
            'student_id' => $ownChild->id, 'guardian_id' => $parentUser->id,
            'relation' => 'parent', 'is_primary' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $ownInvoice   = $this->makeInvoice($ownChild, $year, 'unpaid', 'OWN-' . uniqid());
        $otherInvoice = $this->makeInvoice($other, $year, 'unpaid', 'OTH-' . uniqid());

        $this->actingAs($parentUser)
            ->get(route('invoices.export-excel'))
            ->assertOk();

        Excel::assertDownloaded('invoices-' . now()->format('Y-m-d') . '.xlsx', function (InvoicesListExport $export) use ($ownInvoice, $otherInvoice) {
            $numbers = $export->collection()->pluck('number');
            return $numbers->contains($ownInvoice->number) && !$numbers->contains($otherInvoice->number);
        });
    }

    public function test_invoices_export_pdf_returns_ok(): void
    {
        $year = AcademicYear::create(['name' => 'Y1', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $this->makeInvoice($this->makeStudent('Alice'), $year, 'unpaid', 'INV-' . uniqid());

        $this->actingAs($this->makeUser('accountant'))
            ->get(route('invoices.export-pdf'))
            ->assertOk();
    }
}
