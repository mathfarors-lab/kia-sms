<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\IssuedDocument;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        return $user;
    }

    private function makeStudent(): Student
    {
        return Student::create([
            'user_id' => User::factory()->create()->id, 'student_code' => 'K-' . uniqid(),
            'name_en' => 'Student-' . uniqid(), 'name_km' => 'សិស្ស', 'gender' => 'female', 'status' => 'enrolled',
        ]);
    }

    private function makeUnpaidInvoice(Student $student, string $total): Invoice
    {
        $year = AcademicYear::firstOrCreate(
            ['name' => 'Y1'],
            ['start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]
        );

        return Invoice::create([
            'number' => 'INV-' . uniqid(), 'student_id' => $student->id, 'academic_year_id' => $year->id,
            'term' => 'term_1', 'subtotal' => $total, 'discount' => '0.00', 'total' => $total,
            'paid' => '0.00', 'status' => 'unpaid',
        ]);
    }

    // ── Balance surfacing ────────────────────────────────────────────────────

    public function test_transfer_form_reports_zero_balance_when_none_outstanding(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($this->makeUser('admin'))->get(route('students.transfer.form', $student));

        $response->assertOk();
        $this->assertEquals(0.0, $response->viewData('outstandingBalance'));
    }

    public function test_transfer_form_reports_outstanding_balance_when_present(): void
    {
        $student = $this->makeStudent();
        $this->makeUnpaidInvoice($student, '150.00');

        $response = $this->actingAs($this->makeUser('admin'))->get(route('students.transfer.form', $student));

        $response->assertOk();
        $this->assertEquals(150.0, $response->viewData('outstandingBalance'));
        $response->assertSee('150.00');
    }

    // ── Leaving-certificate regression (critical) ───────────────────────────

    public function test_transfer_without_balance_issues_leaving_certificate_same_as_direct_edit(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($this->makeUser('admin'))->post(route('students.transfer', $student), [
            'reason_category' => 'relocation', 'effective_date' => now()->toDateString(),
            'destination_name' => 'Sunrise International School',
        ]);

        $response->assertRedirect(route('students.show', $student));
        $this->assertEquals('transferred', $student->fresh()->status);
        $this->assertDatabaseHas('issued_documents', [
            'student_id' => $student->id, 'type' => IssuedDocument::TYPE_LEAVING_CERT,
        ]);
    }

    public function test_withdraw_without_balance_issues_leaving_certificate(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($this->makeUser('admin'))->post(route('students.withdraw', $student), [
            'reason_category' => 'financial', 'effective_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('students.show', $student));
        $this->assertEquals('dropped', $student->fresh()->status);
        $this->assertDatabaseHas('issued_documents', [
            'student_id' => $student->id, 'type' => IssuedDocument::TYPE_LEAVING_CERT,
        ]);
    }

    // ── Outstanding balance: surface, don't block, require explicit confirm ─

    public function test_transfer_with_balance_is_blocked_pending_acknowledgement(): void
    {
        $student = $this->makeStudent();
        $this->makeUnpaidInvoice($student, '75.00');

        $response = $this->actingAs($this->makeUser('admin'))->post(route('students.transfer', $student), [
            'reason_category' => 'relocation', 'effective_date' => now()->toDateString(),
            'destination_name' => 'Other School',
            // acknowledge_balance intentionally omitted
        ]);

        $response->assertSessionHasErrors('acknowledge_balance');
        $this->assertEquals('enrolled', $student->fresh()->status);
        $this->assertDatabaseMissing('issued_documents', ['student_id' => $student->id]);
        $this->assertDatabaseMissing('student_transfers', ['student_id' => $student->id]);
    }

    public function test_transfer_with_balance_succeeds_once_acknowledged(): void
    {
        $student = $this->makeStudent();
        $this->makeUnpaidInvoice($student, '75.00');

        $response = $this->actingAs($this->makeUser('admin'))->post(route('students.transfer', $student), [
            'reason_category' => 'relocation', 'effective_date' => now()->toDateString(),
            'destination_name' => 'Other School', 'acknowledge_balance' => '1',
        ]);

        $response->assertRedirect(route('students.show', $student));
        $this->assertEquals('transferred', $student->fresh()->status);
        $this->assertDatabaseHas('student_transfers', [
            'student_id' => $student->id, 'outstanding_balance_at_time' => '75.00',
        ]);
    }

    // ── Logging reason / effective date / destination ───────────────────────

    public function test_transfer_logs_reason_date_and_destination(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->makeUser('admin'))->post(route('students.transfer', $student), [
            'reason_category' => 'academic_fit', 'reason_note' => 'Better program fit elsewhere.',
            'effective_date' => '2026-08-01', 'destination_name' => 'Northside Academy',
        ]);

        $this->assertDatabaseHas('student_transfers', [
            'student_id' => $student->id, 'type' => StudentTransfer::TYPE_TRANSFER,
            'reason_category' => 'academic_fit', 'reason_note' => 'Better program fit elsewhere.',
            'effective_date' => '2026-08-01', 'destination_name' => 'Northside Academy',
        ]);
    }

    public function test_transfer_requires_a_destination(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($this->makeUser('admin'))->post(route('students.transfer', $student), [
            'reason_category' => 'relocation', 'effective_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('destination_branch_id');
        $this->assertEquals('enrolled', $student->fresh()->status);
    }

    /** A second life-event for the same student must not overwrite the first — this is why it's a history table. */
    public function test_multiple_events_for_same_student_are_each_retained(): void
    {
        $student = $this->makeStudent();
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post(route('students.transfer', $student), [
            'reason_category' => 'relocation', 'effective_date' => '2026-01-01', 'destination_name' => 'School A',
        ]);

        // Student re-enrolls elsewhere in the system, then later withdraws for good.
        $student->update(['status' => 'enrolled']);

        $this->actingAs($admin)->post(route('students.withdraw', $student), [
            'reason_category' => 'financial', 'effective_date' => '2026-06-01',
        ]);

        $this->assertEquals(2, StudentTransfer::where('student_id', $student->id)->count());
        $this->assertDatabaseHas('student_transfers', ['student_id' => $student->id, 'type' => 'transfer']);
        $this->assertDatabaseHas('student_transfers', ['student_id' => $student->id, 'type' => 'withdrawal']);
    }

    // ── Authorization matches existing students.edit permission ────────────

    public function test_role_with_students_edit_can_access_both_workflows(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->makeUser('receptionist'))->get(route('students.transfer.form', $student))->assertOk();
        $this->actingAs($this->makeUser('receptionist'))->get(route('students.withdraw.form', $student))->assertOk();
    }

    public function test_role_without_students_edit_is_forbidden(): void
    {
        $student = $this->makeStudent();
        $teacher = $this->makeUser('teacher');

        $this->actingAs($teacher)->get(route('students.transfer.form', $student))->assertForbidden();
        $this->actingAs($teacher)->get(route('students.withdraw.form', $student))->assertForbidden();
        $this->actingAs($teacher)->post(route('students.transfer', $student), [
            'reason_category' => 'relocation', 'effective_date' => now()->toDateString(), 'destination_name' => 'X',
        ])->assertForbidden();
    }
}
