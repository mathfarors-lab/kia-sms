<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AccountantDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Cache::flush();
    }

    private function makeAccountant(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('accountant');
        return $user;
    }

    private function makeStudent(): Student
    {
        $year    = AcademicYear::create(['name' => 'Y-' . uniqid(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $class   = SchoolClass::create(['name' => 'Grade ' . rand(1, 999)]);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);
        $student = Student::create(['student_code' => 'S-' . uniqid(), 'name_en' => 'Student-' . uniqid(), 'gender' => 'male', 'status' => 'enrolled']);
        $section->students()->attach($student->id, ['academic_year_id' => $year->id]);
        return $student;
    }

    private function makeInvoice(Student $student, string $status, float $total, float $paid): Invoice
    {
        return Invoice::create([
            'number' => 'INV-' . uniqid(), 'student_id' => $student->id,
            'academic_year_id' => $student->sections()->first()->pivot->academic_year_id,
            'term' => 'term_1', 'subtotal' => $total, 'discount' => 0, 'total' => $total,
            'paid' => $paid, 'status' => $status,
        ]);
    }

    public function test_dashboard_shows_correct_headline_figures(): void
    {
        $accountant = $this->makeAccountant();

        $paidStudent = $this->makeStudent();
        $paidInvoice = $this->makeInvoice($paidStudent, 'paid', 100, 100);
        Payment::create([
            'invoice_id' => $paidInvoice->id, 'amount' => 100, 'method' => 'cash',
            'received_by' => $accountant->id, 'paid_at' => now(),
        ]);

        $overdueStudent = $this->makeStudent();
        $this->makeInvoice($overdueStudent, 'overdue', 200, 0);

        $partialStudent = $this->makeStudent();
        $this->makeInvoice($partialStudent, 'partial', 150, 50);

        $this->actingAs($accountant)
            ->get(route('dashboard.accountant'))
            ->assertOk()
            ->assertSee('$100.00')      // collected this month
            ->assertSee('$300.00')     // outstanding: (200-0) + (150-50)
            ->assertSee('>1<', false); // overdue_count = 1
    }

    public function test_dashboard_figures_agree_with_finance_dashboard_same_source(): void
    {
        $accountant = $this->makeAccountant();
        $student = $this->makeStudent();
        $invoice = $this->makeInvoice($student, 'paid', 75, 75);
        Payment::create([
            'invoice_id' => $invoice->id, 'amount' => 75, 'method' => 'cash',
            'received_by' => $accountant->id, 'paid_at' => now(),
        ]);

        $dashboardHtml = $this->actingAs($accountant)->get(route('dashboard.accountant'))->assertOk()->getContent();
        $financeHtml    = $this->actingAs($accountant)->get(route('finance.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('$75.00', $dashboardHtml);
        $this->assertStringContainsString('$75.00', $financeHtml);
    }

    public function test_dashboard_has_quick_links_to_invoices_and_fee_structures(): void
    {
        $accountant = $this->makeAccountant();

        $this->actingAs($accountant)
            ->get(route('dashboard.accountant'))
            ->assertOk()
            ->assertSee(route('invoices.index'), false)
            ->assertSee(route('fee-structures.index'), false);
    }
}
