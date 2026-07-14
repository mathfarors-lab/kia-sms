<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\FeeStructure;
use App\Models\Scholarship;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\InvoiceService;
use App\Support\BranchContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiblingDiscountTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private AcademicYear $year;
    private SchoolClass $class;
    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->branch = Branch::findOrFail(1);
        $this->year = AcademicYear::create([
            'name' => 'Y-' . uniqid(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        BranchContext::within($this->branch->id, function () {
            $this->class = SchoolClass::create(['name' => 'Grade 7']);
            $this->section = Section::create(['school_class_id' => $this->class->id, 'name' => 'A']);
            FeeStructure::create(['name' => 'Tuition', 'amount' => 100, 'is_active' => true]);
        });

        BranchContext::clear();
    }

    private function enrollStudent(string $name): Student
    {
        return BranchContext::within($this->branch->id, function () use ($name) {
            $student = Student::create([
                'student_code' => 'S-' . uniqid(), 'name_en' => $name, 'gender' => 'male', 'status' => 'enrolled',
            ]);
            $student->sections()->attach($this->section->id, ['academic_year_id' => $this->year->id]);
            return $student;
        });
    }

    private function attachSharedGuardian(Student ...$students): User
    {
        $guardian = User::factory()->create(['status' => 'active']);
        $guardian->assignRole('parent');
        foreach ($students as $student) {
            $student->guardians()->attach($guardian->id, ['relation' => 'parent', 'is_primary' => true]);
        }
        return $guardian;
    }

    private function generate(): array
    {
        return BranchContext::within($this->branch->id, fn () => app(InvoiceService::class)
            ->generateForClass($this->class, $this->year, 'term_1'));
    }

    public function test_students_with_an_enrolled_sibling_get_the_discount(): void
    {
        $child1 = $this->enrollStudent('Sibling One');
        $child2 = $this->enrollStudent('Sibling Two');
        $this->attachSharedGuardian($child1, $child2);

        $this->generate();

        $invoice1 = \App\Models\Invoice::where('student_id', $child1->id)->firstOrFail();
        $invoice2 = \App\Models\Invoice::where('student_id', $child2->id)->firstOrFail();

        // 10% default off a $100 tuition fee = $10 discount each.
        $this->assertEquals('10.00', $invoice1->discount);
        $this->assertEquals('10.00', $invoice2->discount);
        $this->assertEquals('90.00', $invoice1->total);
    }

    public function test_only_child_with_no_siblings_gets_no_discount(): void
    {
        $onlyChild = $this->enrollStudent('Only Child');

        $this->generate();

        $invoice = \App\Models\Invoice::where('student_id', $onlyChild->id)->firstOrFail();
        $this->assertEquals('0.00', $invoice->discount);
    }

    public function test_sibling_discount_does_not_stack_with_an_existing_manual_scholarship(): void
    {
        $child1 = $this->enrollStudent('Has Scholarship');
        $child2 = $this->enrollStudent('Sibling Of Scholarship Holder');
        $this->attachSharedGuardian($child1, $child2);

        BranchContext::within($this->branch->id, fn () => Scholarship::create([
            'student_id' => $child1->id, 'type' => 'percent', 'value' => 50,
            'reason' => 'Merit', 'is_active' => true, 'is_sibling_discount' => false,
        ]));

        $this->generate();

        $invoice1 = \App\Models\Invoice::where('student_id', $child1->id)->firstOrFail();
        $invoice2 = \App\Models\Invoice::where('student_id', $child2->id)->firstOrFail();

        // Manual 50% scholarship wins for child1 (not stacked with 10% sibling).
        $this->assertEquals('50.00', $invoice1->discount);
        // child2 still gets the sibling discount independently.
        $this->assertEquals('10.00', $invoice2->discount);
    }

    public function test_sibling_discount_percent_is_configurable_per_branch(): void
    {
        BranchContext::within($this->branch->id, fn () => Setting::set('sibling_discount_percent', '25'));

        $child1 = $this->enrollStudent('Sib A');
        $child2 = $this->enrollStudent('Sib B');
        $this->attachSharedGuardian($child1, $child2);

        $this->generate();

        $this->assertEquals('25.00', \App\Models\Invoice::where('student_id', $child1->id)->value('discount'));
    }

    public function test_sibling_discount_can_be_disabled_via_zero_percent(): void
    {
        BranchContext::within($this->branch->id, fn () => Setting::set('sibling_discount_percent', '0'));

        $child1 = $this->enrollStudent('Sib A');
        $child2 = $this->enrollStudent('Sib B');
        $this->attachSharedGuardian($child1, $child2);

        $this->generate();

        $this->assertEquals('0.00', \App\Models\Invoice::where('student_id', $child1->id)->value('discount'));
    }

    public function test_auto_sibling_scholarship_is_deactivated_if_the_sibling_later_leaves(): void
    {
        $child1 = $this->enrollStudent('Sib A');
        $child2 = $this->enrollStudent('Sib B');
        $this->attachSharedGuardian($child1, $child2);

        // First generation run creates the auto sibling-discount scholarship.
        $this->generate();
        $auto = BranchContext::within($this->branch->id, fn () => Scholarship::where('student_id', $child1->id)->where('is_sibling_discount', true)->first());
        $this->assertNotNull($auto);
        $this->assertTrue($auto->is_active);

        // Sibling leaves — no longer "enrolled".
        BranchContext::within($this->branch->id, fn () => $child2->update(['status' => 'dropped']));

        // Re-running generation for a second term re-syncs and must deactivate it.
        BranchContext::within($this->branch->id, fn () => app(InvoiceService::class)
            ->generateForClass($this->class, $this->year, 'term_2'));

        $this->assertFalse($auto->fresh()->is_active);
    }

    public function test_manual_scholarship_is_never_touched_by_the_sync(): void
    {
        $onlyChild = $this->enrollStudent('Only Child With Scholarship');

        $manual = BranchContext::within($this->branch->id, fn () => Scholarship::create([
            'student_id' => $onlyChild->id, 'type' => 'fixed', 'value' => 15,
            'reason' => 'Staff child', 'is_active' => true, 'is_sibling_discount' => false,
        ]));

        $this->generate();

        // No siblings, so sync should do nothing at all to this unrelated row.
        $manual->refresh();
        $this->assertTrue($manual->is_active);
        $this->assertEquals('15.00', $manual->value);
        $this->assertEquals('Staff child', $manual->reason);
    }
}
