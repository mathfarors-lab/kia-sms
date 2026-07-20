<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BillingStatementTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->year = AcademicYear::create([
            'name' => 'Y1', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true,
        ]);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        return $user;
    }

    private function makeStudentUser(): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('student');
        $student = Student::create([
            'user_id' => $user->id, 'student_code' => 'K-' . uniqid(),
            'name_en' => 'Student-' . uniqid(), 'name_km' => 'សិស្ស', 'gender' => 'female', 'status' => 'enrolled',
        ]);
        return [$user, $student];
    }

    private function makeInvoiceWithPayments(Student $student, string $total, array $paymentAmounts, string $number, string $term = 'term_1'): Invoice
    {
        $paid = array_reduce($paymentAmounts, fn ($carry, $amount) => bcadd($carry, $amount, 2), '0.00');

        $invoice = Invoice::create([
            'number' => substr($number, 0, 20), 'student_id' => $student->id, 'academic_year_id' => $this->year->id,
            'term' => $term, 'subtotal' => $total, 'discount' => '0.00', 'total' => $total,
            'paid' => $paid, 'status' => 'unpaid', 'due_date' => '2026-01-01',
        ]);

        foreach ($paymentAmounts as $amount) {
            Payment::create([
                'invoice_id' => $invoice->id, 'amount' => $amount, 'method' => 'cash',
                'received_by' => null, 'paid_at' => now(),
            ]);
        }

        return $invoice;
    }

    // ── Reconciliation ───────────────────────────────────────────────────────

    /** The statement's running balance must exactly equal charges minus payments, bcmath-precise. */
    public function test_statement_reconciles_exactly_against_invoices_and_payments(): void
    {
        $student = $this->makeStudent();

        $inv1 = $this->makeInvoiceWithPayments($student, '100.00', ['40.00'], 'R1-' . uniqid(), 'term_1');
        $inv2 = $this->makeInvoiceWithPayments($student, '50.00', ['20.00', '30.00'], 'R2-' . uniqid(), 'term_2');

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('billing-statement.show', $student));

        $response->assertOk();
        $response->assertViewHas('totalCharged', '150.00');
        $response->assertViewHas('totalPaid', '90.00');
        $response->assertViewHas('balance', '60.00');

        // Cross-check against the Invoice model's own remainingBalance(), which
        // the rest of the app already treats as the source of truth per-invoice.
        $expected = bcadd($inv1->remainingBalance(), $inv2->remainingBalance(), 2);
        $response->assertViewHas('balance', $expected);
    }

    /** Three uneven partial payments (16.67 + 16.67 + 16.66) must still net to a clean zero balance. */
    public function test_statement_reconciles_with_uneven_partial_payments(): void
    {
        $student = $this->makeStudent();
        $this->makeInvoiceWithPayments($student, '50.00', ['16.67', '16.67', '16.66'], 'PT-' . uniqid());

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('billing-statement.show', $student));

        $response->assertViewHas('totalCharged', '50.00');
        $response->assertViewHas('totalPaid', '50.00');
        $response->assertViewHas('balance', '0.00');
    }

    public function test_statement_with_no_invoices_shows_zero_balance(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('billing-statement.show', $student));

        $response->assertOk();
        $response->assertViewHas('balance', '0.00');
        $response->assertSee(__('documents.no_billing_history'));
    }

    // ── Access control ───────────────────────────────────────────────────────

    public function test_admin_accountant_and_principal_can_view_any_statement(): void
    {
        $student = $this->makeStudent();

        foreach (['admin', 'accountant', 'principal'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get(route('billing-statement.show', $student))
                ->assertOk();
        }
    }

    /** Teacher has students.view but is NOT a finance role — must be blocked, mirroring InvoiceController. */
    public function test_teacher_cannot_view_statement(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->makeUser('teacher'))
            ->get(route('billing-statement.show', $student))
            ->assertForbidden();
    }

    public function test_student_can_view_own_statement(): void
    {
        [$user, $student] = $this->makeStudentUser();

        $this->actingAs($user)
            ->get(route('billing-statement.show', $student))
            ->assertOk();
    }

    /** IDOR: a student must never reach another student's billing statement. */
    public function test_student_cannot_view_another_students_statement(): void
    {
        [, $studentA] = $this->makeStudentUser();
        [$userB, ] = $this->makeStudentUser();

        $this->actingAs($userB)
            ->get(route('billing-statement.show', $studentA))
            ->assertForbidden();
    }

    public function test_parent_can_view_own_childs_statement(): void
    {
        $student = $this->makeStudent();
        $parentUser = $this->attachParent($student);

        $this->actingAs($parentUser)
            ->get(route('billing-statement.show', $student))
            ->assertOk();
    }

    /** IDOR: a parent must never reach another family's billing statement. */
    public function test_parent_cannot_view_unrelated_students_statement(): void
    {
        $ownChild = $this->makeStudent();
        $otherChild = $this->makeStudent();
        $parentUser = $this->attachParent($ownChild);

        $this->actingAs($parentUser)
            ->get(route('billing-statement.show', $otherChild))
            ->assertForbidden();
    }

    public function test_pdf_download_returns_ok_for_authorized_staff(): void
    {
        $student = $this->makeStudent();
        $this->makeInvoiceWithPayments($student, '50.00', ['20.00'], 'PD-' . uniqid());

        $this->actingAs($this->makeUser('admin'))
            ->get(route('billing-statement.pdf', $student))
            ->assertOk();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeStudent(): Student
    {
        return Student::create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'student_code' => 'K-' . uniqid(), 'name_en' => 'Student-' . uniqid(),
            'name_km' => 'សិស្ស', 'gender' => 'female', 'status' => 'enrolled',
        ]);
    }

    private function attachParent(Student $student): User
    {
        $parentUser = User::factory()->create(['status' => 'active']);
        $parentUser->assignRole('parent');

        DB::table('student_guardian')->insert([
            'student_id' => $student->id, 'guardian_id' => $parentUser->id,
            'relation' => 'parent', 'is_primary' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $parentUser;
    }
}
