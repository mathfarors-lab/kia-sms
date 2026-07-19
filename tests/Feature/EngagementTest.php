<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TransportRoute;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\LibraryService;
use App\Services\MessagingService;
use App\Services\TransportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected User $parent;
    protected User $student;
    protected Student $studentRecord;
    protected Staff $staffRecord;
    protected Section $section;
    protected AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->admin   = $this->makeUser('admin');
        $this->teacher = $this->makeUser('teacher');
        $this->parent  = $this->makeUser('parent');

        $studentUser = $this->makeUser('student');
        $this->student = $studentUser;

        $this->year    = AcademicYear::create(['name' => 'AY2026', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $class         = SchoolClass::create(['name' => 'Grade 10', 'level' => 'High', 'capacity' => 30]);
        $this->section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);

        $this->studentRecord = Student::create([
            'user_id'      => $studentUser->id,
            'student_code' => 'K-001',
            'name_en'      => 'Alice',
            'name_km'      => 'ស',
            'gender'       => 'female',
            'status'       => 'enrolled',
        ]);

        // Enroll student in section
        DB::table('student_section')->insert([
            'student_id' => $this->studentRecord->id, 'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Link student to parent
        DB::table('student_guardian')->insert([
            'student_id' => $this->studentRecord->id, 'guardian_id' => $this->parent->id,
            'relation' => 'parent', 'is_primary' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Create a Staff record for the teacher
        $this->staffRecord = Staff::create([
            'user_id'    => $this->teacher->id,
            'staff_code' => 'T-001',
            'name_en'    => 'Teacher One',
            'name_km'    => 'គ',
            'gender'     => 'male',
            'role'       => 'teacher',
        ]);
    }

    // ── 1. Announcement audience scoping ────────────────────────────────────

    public function test_parent_sees_all_audience_announcement(): void
    {
        $ann = \App\Models\Announcement::create([
            'title' => 'School Holiday', 'body_en' => 'School closed',
            'audience' => 'all', 'posted_by' => $this->admin->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->parent)->get(route('announcements.index'));
        $response->assertOk()->assertSee('School Holiday');
    }

    public function test_parent_sees_announcement_for_their_childs_section(): void
    {
        $ann = \App\Models\Announcement::create([
            'title' => 'Class Event', 'body_en' => 'Field trip',
            'audience' => 'class', 'target_id' => $this->section->id,
            'posted_by' => $this->admin->id, 'published_at' => now(),
        ]);

        $visible = \App\Models\Announcement::visibleTo($this->parent)->pluck('id');
        $this->assertContains($ann->id, $visible);
    }

    public function test_parent_cannot_see_announcement_for_other_section(): void
    {
        $otherSection = Section::create(['school_class_id' => $this->section->school_class_id, 'name' => 'B']);

        $ann = \App\Models\Announcement::create([
            'title' => 'Other Class', 'body_en' => 'Not for this parent',
            'audience' => 'class', 'target_id' => $otherSection->id,
            'posted_by' => $this->admin->id, 'published_at' => now(),
        ]);

        $visible = \App\Models\Announcement::visibleTo($this->parent)->pluck('id');
        $this->assertNotContains($ann->id, $visible);
    }

    // Regression test — the edit form didn't submit `target_id` at all, and since
    // it's a `nullable` validation rule, FormRequest::validated() still included
    // it as null. Every edit silently wiped target_id, so a class/grade-scoped
    // announcement lost its audience the moment anyone touched the edit form.

    public function test_editing_a_class_announcement_preserves_its_target(): void
    {
        $ann = \App\Models\Announcement::create([
            'title' => 'Field Trip', 'body_en' => 'Bring shoes',
            'audience' => 'class', 'target_id' => $this->section->id,
            'posted_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->put(route('announcements.update', $ann), [
                'title'     => 'Field Trip (updated)',
                'body_en'   => 'Bring shoes and a hat',
                'audience'  => 'class',
                'target_id' => $this->section->id,
            ])
            ->assertRedirect(route('announcements.show', $ann));

        $this->assertEquals($this->section->id, $ann->fresh()->target_id);
    }

    // ── 2. Messaging: IDOR + scope ──────────────────────────────────────────

    public function test_non_participant_cannot_view_conversation(): void
    {
        $service  = new MessagingService();
        $stranger = $this->makeUser('teacher');

        $conv = $service->createConversation($this->admin, $this->teacher, 'Hello', 'Hi there');

        // Stranger (another teacher, not a participant) gets 403
        $response = $this->actingAs($stranger)->get(route('conversations.show', $conv));
        $response->assertForbidden();
    }

    public function test_out_of_scope_recipient_is_rejected(): void
    {
        // Teacher trying to message a student NOT in their sections
        $outsideStudent = $this->makeUser('student');
        $service = new MessagingService();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->createConversation($this->teacher, $outsideStudent, 'Hey', 'Hello');
    }

    public function test_teacher_can_message_parent_of_their_student(): void
    {
        // Link timetable: teacher teaches in the student's section
        DB::table('timetables')->insert([
            'section_id' => $this->section->id,
            'subject_id' => Subject::create(['name_en' => 'Math', 'name_km' => 'គណិត', 'code' => 'MATH'])->id,
            'teacher_id' => $this->staffRecord->id,
            'day' => 'monday', 'period' => 1,
            'start_time' => '07:00', 'end_time' => '08:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = new MessagingService();
        $this->assertTrue($service->canMessage($this->teacher, $this->parent));
    }

    // ── 3. Homework ─────────────────────────────────────────────────────────

    public function test_homework_late_flag_is_set_when_submitted_after_due_date(): void
    {
        $hw = Homework::create([
            'section_id'  => $this->section->id,
            'subject_id'  => Subject::create(['name_en' => 'Sci', 'name_km' => 'វិទ', 'code' => 'SCI'])->id,
            'teacher_id'  => $this->staffRecord->id,
            'title'       => 'Essay',
            'due_date'    => now()->subDay()->toDateString(),
            'published_at' => now()->subDays(3),
        ]);

        $this->assertTrue($hw->isLate(now()));
    }

    public function test_homework_not_late_when_submitted_on_due_date(): void
    {
        $hw = Homework::create([
            'section_id'  => $this->section->id,
            'subject_id'  => Subject::create(['name_en' => 'Sci2', 'name_km' => 'វ', 'code' => 'SC2'])->id,
            'teacher_id'  => $this->staffRecord->id,
            'title'       => 'Essay 2',
            'due_date'    => today()->toDateString(),
            'published_at' => now()->subDay(),
        ]);

        $this->assertFalse($hw->isLate(now()->startOfDay()));
    }

    public function test_student_cannot_submit_homework_for_another_student(): void
    {
        $otherStudentUser = $this->makeUser('student');
        $otherStudent     = Student::create([
            'user_id' => $otherStudentUser->id, 'student_code' => 'K-002',
            'name_en' => 'Bob', 'name_km' => 'ប', 'gender' => 'male', 'status' => 'enrolled',
        ]);

        $hw = Homework::create([
            'section_id'  => $this->section->id,
            'subject_id'  => Subject::create(['name_en' => 'Hist', 'name_km' => 'ប', 'code' => 'HIS'])->id,
            'teacher_id'  => $this->staffRecord->id,
            'title'       => 'History Essay',
            'due_date'    => now()->addWeek()->toDateString(),
            'published_at' => now(),
        ]);

        // otherStudentUser is NOT enrolled in the section — policy should deny
        $response = $this->actingAs($otherStudentUser)
            ->post(route('homework.submit', $hw), ['note' => 'test']);
        $response->assertForbidden();
    }

    public function test_teacher_can_only_grade_their_own_section_homework(): void
    {
        $otherTeacherUser = $this->makeUser('teacher');
        $otherStaff       = Staff::create([
            'user_id'    => $otherTeacherUser->id,
            'staff_code' => 'T-002', 'name_en' => 'Other', 'name_km' => 'អ',
            'gender' => 'male', 'role' => 'teacher',
        ]);

        $hw = Homework::create([
            'section_id'  => $this->section->id,
            'subject_id'  => Subject::create(['name_en' => 'Art', 'name_km' => 'ស', 'code' => 'ART'])->id,
            'teacher_id'  => $this->staffRecord->id, // belongs to $this->teacher
            'title'       => 'Art Project',
            'due_date'    => now()->addWeek()->toDateString(),
            'published_at' => now(),
        ]);

        $sub = HomeworkSubmission::create([
            'homework_id' => $hw->id,
            'student_id'  => $this->studentRecord->id,
            'is_late'     => false,
            'submitted_at' => now(),
        ]);

        // Another teacher (different staff) tries to grade it
        $response = $this->actingAs($otherTeacherUser)
            ->post(route('homework-submissions.grade', $sub), ['grade' => 90, 'feedback' => 'Good']);
        $response->assertForbidden();
    }

    // Regression — index()/show()/download() had no authorize() call at all, so any
    // authenticated user of any role (including ones holding zero homework permission)
    // could browse and download every homework in the school.

    public function test_role_with_no_homework_permission_cannot_view_homework(): void
    {
        $hw = Homework::create([
            'section_id'  => $this->section->id,
            'subject_id'  => Subject::create(['name_en' => 'Geo', 'name_km' => 'ភ', 'code' => 'GEO'])->id,
            'teacher_id'  => $this->staffRecord->id,
            'title'       => 'Geography Map',
            'due_date'    => now()->addWeek()->toDateString(),
            'published_at' => now(),
        ]);

        foreach (['parent', 'accountant', 'librarian', 'receptionist'] as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)->get(route('homework.index'))->assertForbidden();
            $this->actingAs($user)->get(route('homework.show', $hw))->assertForbidden();
            $this->actingAs($user)->get(route('homework.download', $hw))->assertForbidden();
        }
    }

    public function test_student_cannot_view_homework_outside_their_section(): void
    {
        $otherSection = Section::create(['school_class_id' => $this->section->school_class_id, 'name' => 'B']);

        $hw = Homework::create([
            'section_id'  => $otherSection->id,
            'subject_id'  => Subject::create(['name_en' => 'Music', 'name_km' => 'ត', 'code' => 'MUS'])->id,
            'teacher_id'  => $this->staffRecord->id,
            'title'       => 'Music Theory',
            'due_date'    => now()->addWeek()->toDateString(),
            'published_at' => now(),
        ]);

        $this->actingAs($this->student)->get(route('homework.show', $hw))->assertForbidden();
    }

    public function test_student_cannot_view_unpublished_draft_homework(): void
    {
        $draft = Homework::create([
            'section_id'   => $this->section->id,
            'subject_id'   => Subject::create(['name_en' => 'Draft', 'name_km' => 'ព', 'code' => 'DFT'])->id,
            'teacher_id'   => $this->staffRecord->id,
            'title'        => 'Not Yet Published',
            'due_date'     => now()->addWeek()->toDateString(),
            'published_at' => null,
        ]);

        // Student is enrolled in the section, but the homework is still a draft.
        $this->actingAs($this->student)->get(route('homework.show', $draft))->assertForbidden();
    }

    // ── 4. Library ──────────────────────────────────────────────────────────

    public function test_cannot_issue_book_when_no_copies_available(): void
    {
        $book = Book::create([
            'title' => 'Rare Book', 'total_copies' => 1, 'available_copies' => 0,
        ]);

        $this->expectException(ValidationException::class);

        $service = new LibraryService();
        $service->issue($book, $this->studentRecord, $this->admin, now()->addWeek()->toDateString());
    }

    public function test_issuing_decrements_available_copies(): void
    {
        $book = Book::create([
            'title' => 'Popular Book', 'total_copies' => 3, 'available_copies' => 3,
        ]);

        $service = new LibraryService();
        $service->issue($book, $this->studentRecord, $this->admin, now()->addWeek()->toDateString());

        $book->refresh();
        $this->assertEquals(2, $book->available_copies);
    }

    public function test_concurrent_issue_of_last_copy_is_guarded(): void
    {
        $book = Book::create([
            'title' => 'Last Copy', 'total_copies' => 1, 'available_copies' => 1,
        ]);

        $service = new LibraryService();

        // Issue the last copy once
        $service->issue($book, $this->studentRecord, $this->admin, now()->addWeek()->toDateString());

        // Second attempt must fail
        $student2 = Student::create([
            'user_id' => $this->makeUser('student')->id,
            'student_code' => 'K-003', 'name_en' => 'Carl', 'name_km' => 'ក',
            'gender' => 'male', 'status' => 'enrolled',
        ]);

        $this->expectException(ValidationException::class);
        $service->issue($book, $student2, $this->admin, now()->addWeek()->toDateString());
    }

    public function test_fine_calculated_correctly_on_return(): void
    {
        // Fine rate from settings — default 0.50/day
        \App\Models\Setting::updateOrCreate(['key' => 'library_fine_per_day'], ['value' => '0.50']);

        $book = Book::create([
            'title' => 'Overdue Book', 'total_copies' => 1, 'available_copies' => 1,
        ]);

        $service = new LibraryService();
        $issue   = $service->issue($book, $this->studentRecord, $this->admin,
            now()->subDays(5)->toDateString() // due 5 days ago
        );

        $issue = $service->returnBook($issue);

        // 5 days × $0.50 = $2.50
        $this->assertEquals('2.50', $issue->fine_amount);
    }

    // ── 5. Transport ─────────────────────────────────────────────────────────

    public function test_capacity_guard_rejects_over_assignment(): void
    {
        $route   = TransportRoute::create(['name' => 'Route A', 'fare' => '20.00']);
        $vehicle = Vehicle::create([
            'route_id' => $route->id, 'plate_no' => 'PP-001',
            'driver_name' => 'Dara', 'capacity' => 1,
        ]);

        // Fill the vehicle
        $service = new TransportService();
        $service->assign($this->studentRecord, $vehicle, $this->year);

        // Second student
        $student2 = Student::create([
            'user_id' => $this->makeUser('student')->id,
            'student_code' => 'K-004', 'name_en' => 'Dan', 'name_km' => 'ដ',
            'gender' => 'male', 'status' => 'enrolled',
        ]);

        $this->expectException(ValidationException::class);
        $service->assign($student2, $vehicle, $this->year);
    }

    // ── 6. File upload validation ────────────────────────────────────────────

    public function test_oversized_file_is_rejected(): void
    {
        Storage::fake('local');

        $hw = Homework::create([
            'section_id'  => $this->section->id,
            'subject_id'  => Subject::create(['name_en' => 'PE', 'name_km' => 'ព', 'code' => 'PE'])->id,
            'teacher_id'  => $this->staffRecord->id,
            'title'       => 'Run Essay',
            'due_date'    => now()->addWeek()->toDateString(),
            'published_at' => now(),
        ]);

        // Enroll student so policy passes
        $response = $this->actingAs($this->student)
            ->post(route('homework.submit', $hw), [
                'file' => UploadedFile::fake()->create('big.pdf', 11 * 1024), // 11 MB
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        Storage::fake('local');

        $hw = Homework::create([
            'section_id'  => $this->section->id,
            'subject_id'  => Subject::create(['name_en' => 'Comp', 'name_km' => 'ក', 'code' => 'CP'])->id,
            'teacher_id'  => $this->staffRecord->id,
            'title'       => 'Code Project',
            'due_date'    => now()->addWeek()->toDateString(),
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('homework.submit', $hw), [
                'file' => UploadedFile::fake()->create('malware.exe', 100),
            ]);

        $response->assertSessionHasErrors('file');
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function makeUser(string $role): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole($role);
        return $u;
    }
}
