<?php

namespace Tests\Feature;

use App\Console\Commands\MarkOverdueInvoices;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceSequence;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $accountant;
    protected AcademicYear $year;
    protected SchoolClass $class;
    protected Section $section;
    protected Student $student;
    protected FeeStructure $fee;
    protected InvoiceService $invoiceService;
    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');

        $this->accountant = User::factory()->create(['status' => 'active']);
        $this->accountant->assignRole('accountant');

        $this->year    = AcademicYear::create(['name' => 'FY', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $this->class   = SchoolClass::create(['name' => 'Grade 10', 'level' => 'High', 'capacity' => 30]);
        $this->section = Section::create(['school_class_id' => $this->class->id, 'name' => 'A']);

        $sUser = User::factory()->create(['status' => 'active']);
        $this->student = Student::create(['user_id' => $sUser->id, 'student_code' => 'K-0001', 'name_en' => 'Alice', 'name_km' => 'ស', 'gender' => 'female', 'status' => 'enrolled']);

        DB::table('student_section')->insert([
            'student_id'       => $this->student->id,
            'section_id'       => $this->section->id,
            'academic_year_id' => $this->year->id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->fee = FeeStructure::create(['name' => 'Tuition', 'amount' => '50.00', 'frequency' => 'term', 'is_active' => true]);

        $this->invoiceService = new InvoiceService();
        $this->paymentService = new PaymentService();
    }

    // --- Invoice generation ---

    public function test_generate_creates_invoice_for_enrolled_students(): void
    {
        $result = $this->invoiceService->generateForClass($this->class, $this->year, 'term_1');

        $this->assertEquals(1, $result['created']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertDatabaseHas('invoices', [
            'student_id'       => $this->student->id,
            'academic_year_id' => $this->year->id,
            'term'             => 'term_1',
            'total'            => '50.00',
        ]);
    }

    public function test_generation_is_idempotent(): void
    {
        $r1 = $this->invoiceService->generateForClass($this->class, $this->year, 'term_1');
        $r2 = $this->invoiceService->generateForClass($this->class, $this->year, 'term_1');

        $this->assertEquals(1, $r1['created']);
        $this->assertEquals(0, $r2['created']);
        $this->assertEquals(1, $r2['skipped']);
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_invoice_numbers_are_sequential_and_unique(): void
    {
        // Two students in the same class
        $sUser2   = User::factory()->create(['status' => 'active']);
        $student2 = Student::create(['user_id' => $sUser2->id, 'student_code' => 'K-0002', 'name_en' => 'Bob', 'name_km' => 'ប', 'gender' => 'male', 'status' => 'enrolled']);
        DB::table('student_section')->insert([
            'student_id' => $student2->id, 'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->invoiceService->generateForClass($this->class, $this->year, 'term_1');

        $numbers = Invoice::pluck('number')->sort()->values();
        $this->assertCount(2, $numbers);
        $this->assertStringStartsWith('INV-', $numbers[0]);
        $this->assertNotEquals($numbers[0], $numbers[1]);
    }

    // --- Partial payment rounding ---

    public function test_three_partial_payments_settle_to_zero(): void
    {
        // This is the 16.67 + 16.67 + 16.66 = 50.00 case
        $inv = $this->makeInvoice('50.00');

        $this->paymentService->record($inv, '16.67', 'cash', null, $this->accountant);
        $inv->refresh();
        $this->assertEquals('16.67', $inv->paid);

        $this->paymentService->record($inv, '16.67', 'cash', null, $this->accountant);
        $inv->refresh();
        $this->assertEquals('33.34', $inv->paid);

        $this->paymentService->record($inv, '16.66', 'cash', null, $this->accountant);
        $inv->refresh();
        $this->assertEquals('50.00', $inv->paid);
        $this->assertEquals('0.00', $inv->remainingBalance());
        $this->assertEquals('paid', $inv->status);
    }

    // --- Overpayment rejection ---

    public function test_overpayment_is_rejected(): void
    {
        $inv = $this->makeInvoice('50.00');

        $this->expectException(ValidationException::class);
        $this->paymentService->record($inv, '60.00', 'cash', null, $this->accountant);
    }

    public function test_payment_on_paid_invoice_is_rejected(): void
    {
        $inv = $this->makeInvoice('50.00');
        $this->paymentService->record($inv, '50.00', 'cash', null, $this->accountant);
        $inv->refresh();

        $this->expectException(ValidationException::class);
        $this->paymentService->record($inv, '1.00', 'cash', null, $this->accountant);
    }

    // --- Status transitions ---

    public function test_status_transitions_unpaid_partial_paid(): void
    {
        $inv = $this->makeInvoice('100.00');
        $this->assertEquals('unpaid', $inv->status);

        $this->paymentService->record($inv, '50.00', 'cash', null, $this->accountant);
        $inv->refresh();
        $this->assertEquals('partial', $inv->status);

        $this->paymentService->record($inv, '50.00', 'cash', null, $this->accountant);
        $inv->refresh();
        $this->assertEquals('paid', $inv->status);
    }

    public function test_overdue_command_marks_past_due_invoices(): void
    {
        $inv = $this->makeInvoice('50.00', Carbon::yesterday()->subDay());
        $this->assertEquals('unpaid', $inv->status);

        $this->artisan('invoices:mark-overdue');

        $inv->refresh();
        $this->assertEquals('overdue', $inv->status);
    }

    public function test_overdue_command_leaves_paid_invoices_untouched(): void
    {
        $inv = $this->makeInvoice('50.00', Carbon::yesterday());
        $this->paymentService->record($inv, '50.00', 'cash', null, $this->accountant);
        $inv->refresh();

        $this->artisan('invoices:mark-overdue');

        $inv->refresh();
        $this->assertEquals('paid', $inv->status);
    }

    // --- Authorization ---

    public function test_accountant_can_view_invoice_list(): void
    {
        $this->makeInvoice('50.00');

        $response = $this->actingAs($this->accountant)->get(route('invoices.index'));
        $response->assertOk();
    }

    public function test_parent_sees_only_own_child_invoices(): void
    {
        $parentUser = User::factory()->create(['status' => 'active']);
        $parentUser->assignRole('parent');

        DB::table('student_guardian')->insert([
            'student_id' => $this->student->id, 'guardian_id' => $parentUser->id,
            'relation' => 'parent', 'is_primary' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $inv = $this->makeInvoice('50.00');

        // Parent can see their child's invoice
        $response = $this->actingAs($parentUser)->get(route('invoices.show', $inv));
        $response->assertOk();

        // Parent cannot see another student's invoice
        $other  = User::factory()->create(['status' => 'active']);
        $sOther = Student::create(['user_id' => $other->id, 'student_code' => 'K-9999', 'name_en' => 'X', 'name_km' => 'X', 'gender' => 'male', 'status' => 'enrolled']);
        $otherInv = Invoice::create([
            'number' => 'INV-2026-99999', 'student_id' => $sOther->id,
            'academic_year_id' => $this->year->id, 'term' => 'other_term',
            'subtotal' => '50.00', 'discount' => '0.00', 'total' => '50.00',
            'paid' => '0.00', 'status' => 'unpaid',
        ]);
        $response2 = $this->actingAs($parentUser)->get(route('invoices.show', $otherInv));
        $response2->assertForbidden();
    }

    public function test_teacher_is_blocked_from_finance(): void
    {
        $teacher = User::factory()->create(['status' => 'active']);
        $teacher->assignRole('teacher');

        $this->makeInvoice('50.00');

        $response = $this->actingAs($teacher)->get(route('invoices.index'));
        $response->assertForbidden();
    }

    // --- Helpers ---

    private function makeInvoice(string $total, ?Carbon $dueDate = null): Invoice
    {
        $year = (int) now()->format('Y');
        $seq  = InvoiceSequence::firstOrCreate(['year' => $year], ['last_number' => 0]);
        $seq->increment('last_number');
        $seq->refresh();
        $number = 'INV-' . $year . '-' . str_pad($seq->last_number, 5, '0', STR_PAD_LEFT);

        return Invoice::create([
            'number'           => $number,
            'student_id'       => $this->student->id,
            'academic_year_id' => $this->year->id,
            'term'             => 'test_term_' . uniqid(),
            'subtotal'         => $total,
            'discount'         => '0.00',
            'total'            => $total,
            'paid'             => '0.00',
            'status'           => 'unpaid',
            'due_date'         => $dueDate,
        ]);
    }
}
