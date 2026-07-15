<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\IssuedDocument;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Support\BranchContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * A student that predates auto-issue: created via a bare Eloquent
     * create() the way pre-existing rows actually are, bypassing
     * StudentService::store() entirely — so no IssuedDocument exists yet.
     */
    private function makePreExistingStudent(string $status = 'enrolled', ?int $branchId = 1): Student
    {
        return BranchContext::within($branchId, fn () => Student::create([
            'student_code' => 'PRE-' . uniqid(),
            'name_en'      => 'Backfill Target ' . uniqid(),
            'gender'       => 'male',
            'status'       => $status,
        ]));
    }

    private function makePreExistingStaff(?int $branchId = 1): Staff
    {
        $user = User::factory()->create(['status' => 'active']);
        return BranchContext::within($branchId, fn () => Staff::create([
            'user_id'    => $user->id,
            'staff_code' => 'PRE-' . uniqid(),
        ]));
    }

    public function test_backfills_id_card_and_enrollment_cert_for_a_pre_existing_enrolled_student(): void
    {
        $student = $this->makePreExistingStudent('enrolled');
        $this->assertDatabaseMissing('issued_documents', ['student_id' => $student->id]);

        $this->artisan('kia:backfill-documents')->assertSuccessful();

        $this->assertDatabaseHas('issued_documents', ['student_id' => $student->id, 'type' => IssuedDocument::TYPE_ID_CARD]);
        $this->assertDatabaseHas('issued_documents', ['student_id' => $student->id, 'type' => IssuedDocument::TYPE_ENROLLMENT_CERT]);
    }

    public function test_backfills_graduation_certificate_for_a_pre_existing_graduated_student(): void
    {
        $student = $this->makePreExistingStudent('graduated');

        $this->artisan('kia:backfill-documents')->assertSuccessful();

        $this->assertDatabaseHas('issued_documents', ['student_id' => $student->id, 'type' => IssuedDocument::TYPE_GRADUATION_CERT]);
        $this->assertDatabaseMissing('issued_documents', ['student_id' => $student->id, 'type' => IssuedDocument::TYPE_ID_CARD]);
    }

    public function test_backfills_leaving_certificate_for_pre_existing_transferred_and_dropped_students(): void
    {
        $transferred = $this->makePreExistingStudent('transferred');
        $dropped     = $this->makePreExistingStudent('dropped');

        $this->artisan('kia:backfill-documents')->assertSuccessful();

        $this->assertDatabaseHas('issued_documents', ['student_id' => $transferred->id, 'type' => IssuedDocument::TYPE_LEAVING_CERT]);
        $this->assertDatabaseHas('issued_documents', ['student_id' => $dropped->id, 'type' => IssuedDocument::TYPE_LEAVING_CERT]);
    }

    public function test_backfills_id_card_for_pre_existing_staff(): void
    {
        $staff = $this->makePreExistingStaff();
        $this->assertDatabaseMissing('issued_documents', ['staff_id' => $staff->id]);

        $this->artisan('kia:backfill-documents')->assertSuccessful();

        $this->assertDatabaseHas('issued_documents', ['staff_id' => $staff->id, 'type' => IssuedDocument::TYPE_ID_CARD]);
    }

    public function test_running_twice_does_not_duplicate_any_document(): void
    {
        $student = $this->makePreExistingStudent('enrolled');
        $staff   = $this->makePreExistingStaff();

        $this->artisan('kia:backfill-documents');
        $this->artisan('kia:backfill-documents');

        $this->assertEquals(1, IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_ID_CARD)->count());
        $this->assertEquals(1, IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_ENROLLMENT_CERT)->count());
        $this->assertEquals(1, IssuedDocument::where('staff_id', $staff->id)->count());
    }

    public function test_running_again_does_not_renumber_an_already_issued_certificate(): void
    {
        $student = $this->makePreExistingStudent('graduated');

        $this->artisan('kia:backfill-documents');
        $firstNumber = IssuedDocument::where('student_id', $student->id)->value('number');

        $this->artisan('kia:backfill-documents');
        $secondNumber = IssuedDocument::where('student_id', $student->id)->value('number');

        $this->assertNotNull($firstNumber);
        $this->assertEquals($firstNumber, $secondNumber);
    }

    /** Confirms it never touches a document that a normal (non-backfill) flow already issued. */
    public function test_does_not_touch_a_document_already_issued_through_the_normal_flow(): void
    {
        $student = $this->makePreExistingStudent('enrolled');

        // Simulate the normal auto-issue path having already run for this student.
        $existing = app(\App\Services\DocumentIssuanceService::class)
            ->issueForStudent($student, IssuedDocument::TYPE_ENROLLMENT_CERT);

        $this->artisan('kia:backfill-documents');

        $this->assertEquals($existing->id, IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_ENROLLMENT_CERT)->value('id'));
        $this->assertEquals(1, IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_ENROLLMENT_CERT)->count());
    }

    public function test_documents_are_stamped_with_the_students_own_branch_not_a_null_or_wrong_one(): void
    {
        $riverside = Branch::create(['name_en' => 'Riverside Campus', 'code' => 'RC', 'is_active' => true]);
        $student   = $this->makePreExistingStudent('enrolled', $riverside->id);

        $this->artisan('kia:backfill-documents');

        $doc = IssuedDocument::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)
            ->where('student_id', $student->id)->where('type', IssuedDocument::TYPE_ID_CARD)->first();

        $this->assertEquals($riverside->id, $doc->branch_id);
    }

    public function test_reports_a_summary_of_what_it_did(): void
    {
        $this->makePreExistingStudent('enrolled');
        $this->makePreExistingStaff();

        $this->artisan('kia:backfill-documents')
            ->assertSuccessful()
            ->expectsOutputToContain('issued')
            ->expectsOutputToContain('Safe to run again');
    }
}
