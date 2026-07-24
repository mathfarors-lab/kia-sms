<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /**
     * Route::resource('students', ...) registers GET /students/{student}.
     * If /students/import is declared after it, "import" gets swallowed by
     * the {student} wildcard and route-model binding 404s before the
     * request ever reaches StudentImportController — regardless of auth.
     */
    public function test_import_form_route_is_not_shadowed_by_the_students_resource_route(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('students.import'))
            ->assertOk();
    }

    public function test_user_without_students_create_permission_cannot_view_import_form(): void
    {
        $teacher = User::factory()->create(['status' => 'active']);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)
            ->get(route('students.import'))
            ->assertForbidden();
    }

    public function test_template_downloads_as_a_real_xlsx(): void
    {
        $response = $this->actingAs($this->makeAdmin())->get(route('students.import.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // ── Actual import behavior, run through the real pipeline ────────────────

    public function test_import_creates_students_with_no_class_or_section_columns(): void
    {
        $file = $this->buildImportFile([
            ['student_code' => 'K-1001', 'name_en' => 'Sok Dara', 'name_km' => '', 'gender' => 'male', 'date_of_birth' => '', 'address' => '', 'class_name' => '', 'section_name' => ''],
        ]);

        $this->actingAs($this->makeAdmin())
            ->post(route('students.import.store'), ['file' => $file])
            ->assertRedirect(route('students.index'));

        $student = Student::where('student_code', 'K-1001')->first();
        $this->assertNotNull($student);
        $this->assertCount(0, $student->sections);
    }

    public function test_import_assigns_class_and_section_when_both_columns_match_real_records(): void
    {
        $year = AcademicYear::create(['name' => 'Y1', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $class = SchoolClass::create(['name' => 'Grade 5', 'level' => 'Grade 5', 'capacity' => 30]);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'Section A']);

        $file = $this->buildImportFile([
            ['student_code' => 'K-1002', 'name_en' => 'Sok Dara', 'name_km' => '', 'gender' => 'male', 'date_of_birth' => '', 'address' => '', 'class_name' => 'Grade 5', 'section_name' => 'Section A'],
        ]);

        $this->actingAs($this->makeAdmin())
            ->post(route('students.import.store'), ['file' => $file]);

        $student = Student::where('student_code', 'K-1002')->first();
        $this->assertNotNull($student);
        $this->assertTrue($student->sections()->wherePivot('academic_year_id', $year->id)->where('sections.id', $section->id)->exists());
    }

    public function test_import_rejects_a_row_with_only_class_name_and_creates_no_student(): void
    {
        SchoolClass::create(['name' => 'Grade 5', 'level' => 'Grade 5', 'capacity' => 30]);

        $file = $this->buildImportFile([
            ['student_code' => 'K-1003', 'name_en' => 'Sok Dara', 'name_km' => '', 'gender' => 'male', 'date_of_birth' => '', 'address' => '', 'class_name' => 'Grade 5', 'section_name' => ''],
        ]);

        $response = $this->actingAs($this->makeAdmin())
            ->post(route('students.import.store'), ['file' => $file]);

        $response->assertSessionHas('errors');
        $this->assertNull(Student::where('student_code', 'K-1003')->first());
    }

    public function test_import_rejects_a_row_referencing_a_section_that_does_not_exist(): void
    {
        SchoolClass::create(['name' => 'Grade 5', 'level' => 'Grade 5', 'capacity' => 30]);

        $file = $this->buildImportFile([
            ['student_code' => 'K-1004', 'name_en' => 'Sok Dara', 'name_km' => '', 'gender' => 'male', 'date_of_birth' => '', 'address' => '', 'class_name' => 'Grade 5', 'section_name' => 'Section Z'],
        ]);

        $this->actingAs($this->makeAdmin())
            ->post(route('students.import.store'), ['file' => $file])
            ->assertSessionHas('errors');

        $this->assertNull(Student::where('student_code', 'K-1004')->first());
    }

    public function test_import_rejects_class_section_when_no_active_academic_year_exists(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 5', 'level' => 'Grade 5', 'capacity' => 30]);
        Section::create(['school_class_id' => $class->id, 'name' => 'Section A']);
        // Deliberately no AcademicYear::create() with is_active => true.

        $file = $this->buildImportFile([
            ['student_code' => 'K-1005', 'name_en' => 'Sok Dara', 'name_km' => '', 'gender' => 'male', 'date_of_birth' => '', 'address' => '', 'class_name' => 'Grade 5', 'section_name' => 'Section A'],
        ]);

        $this->actingAs($this->makeAdmin())
            ->post(route('students.import.store'), ['file' => $file])
            ->assertSessionHas('errors');

        $this->assertNull(Student::where('student_code', 'K-1005')->first());
    }

    public function test_import_still_rejects_duplicate_student_codes(): void
    {
        Student::create(['student_code' => 'K-1006', 'name_en' => 'Existing', 'gender' => 'male', 'status' => 'enrolled']);

        $file = $this->buildImportFile([
            ['student_code' => 'K-1006', 'name_en' => 'Sok Dara', 'name_km' => '', 'gender' => 'male', 'date_of_birth' => '', 'address' => '', 'class_name' => '', 'section_name' => ''],
        ]);

        $this->actingAs($this->makeAdmin())
            ->post(route('students.import.store'), ['file' => $file])
            ->assertSessionHas('errors');

        $this->assertSame(1, Student::where('student_code', 'K-1006')->count());
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        return $admin;
    }

    /** Builds a real .xlsx (via the same Maatwebsite\Excel pipeline the template uses) and wraps it as an uploaded file. */
    private function buildImportFile(array $rows): UploadedFile
    {
        $export = new class($rows) implements FromArray, WithHeadings {
            public function __construct(private array $rows) {}
            public function headings(): array
            {
                return ['student_code', 'name_en', 'name_km', 'gender', 'date_of_birth', 'address', 'class_name', 'section_name'];
            }
            public function array(): array
            {
                return array_map(fn ($r) => array_values($r), $this->rows);
            }
        };

        $path = 'test-imports/' . uniqid() . '.xlsx';
        Excel::store($export, $path, 'local');
        $fullPath = storage_path('app/private/' . $path);
        if (!file_exists($fullPath)) {
            $fullPath = storage_path('app/' . $path); // disk root varies by Laravel version config
        }

        return new UploadedFile($fullPath, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
