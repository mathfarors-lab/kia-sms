<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The sidebar is permission-driven: every role must see a link to everything
 * it can reach, and must NOT see links to pages its permissions don't cover.
 * Each test renders the role's own dashboard (which includes the sidebar)
 * and asserts on the presence/absence of link URLs.
 */
class SidebarNavigationTest extends TestCase
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

    private function dashboardHtml(string $role): string
    {
        return $this->actingAs($this->makeUser($role))
            ->followingRedirects()
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();
    }

    public function test_admin_sees_every_module_but_no_portal_links(): void
    {
        $html = $this->dashboardHtml('admin');

        foreach ([
            'students.index', 'staff.index', 'academic-years.index', 'classes.index',
            'subjects.index', 'grade-scales.index', 'attendance.index', 'promotion.index',
            'exams.index', 'exam-marks.index', 'term-results.index',
            'invoices.index', 'fee-structures.index', 'scholarships.index',
            'finance.dashboard', 'finance.report',
            'announcements.index', 'conversations.index', 'homework.index',
            'books.index', 'transport.routes.index', 'leaves.index',
            'analytics.index', 'reports.index', 'audit.index', 'admin.bakong.failed',
            'users.index', 'settings.index',
        ] as $routeName) {
            $this->assertStringContainsString(route($routeName), $html, "admin should see {$routeName}");
        }

        // Portal sections belong to student/parent roles only
        $this->assertStringNotContainsString(route('parent.children'), $html);
        $this->assertStringNotContainsString(route('student.attendance'), $html);
    }

    public function test_principal_sees_leadership_modules_but_not_user_management(): void
    {
        $html = $this->dashboardHtml('principal');

        foreach ([
            'students.index', 'staff.index', 'academic-years.index', 'subjects.index',
            'exams.index', 'term-results.index', 'promotion.index',
            'invoices.index', 'finance.dashboard',
            'announcements.index', 'conversations.index', 'leaves.index',
            'analytics.index', 'reports.index', 'audit.index', 'settings.index',
            'homework.index', // view-only oversight via HOMEWORK_VIEW (Phase 1.5b decision)
        ] as $routeName) {
            $this->assertStringContainsString(route($routeName), $html, "principal should see {$routeName}");
        }

        $this->assertStringNotContainsString(route('users.index'), $html);
    }

    public function test_teacher_sees_teaching_modules_but_not_finance_or_system(): void
    {
        $html = $this->dashboardHtml('teacher');

        foreach ([
            'students.index', 'attendance.index',
            'exams.index', 'exam-marks.index', 'term-results.index',
            'announcements.index', 'conversations.index', 'homework.index',
            'books.index', 'leaves.index',
        ] as $routeName) {
            $this->assertStringContainsString(route($routeName), $html, "teacher should see {$routeName}");
        }

        foreach (['invoices.index', 'fee-structures.index', 'users.index', 'settings.index', 'audit.index', 'promotion.index'] as $routeName) {
            $this->assertStringNotContainsString(route($routeName), $html, "teacher should NOT see {$routeName}");
        }
    }

    public function test_accountant_sees_finance_but_not_academic_consoles(): void
    {
        $html = $this->dashboardHtml('accountant');

        foreach ([
            'students.index', 'invoices.index', 'fee-structures.index', 'scholarships.index',
            'finance.dashboard', 'finance.report', 'reports.index',
            'conversations.index', 'leaves.index',
        ] as $routeName) {
            $this->assertStringContainsString(route($routeName), $html, "accountant should see {$routeName}");
        }

        foreach (['exams.index', 'attendance.index', 'books.index', 'users.index', 'audit.index'] as $routeName) {
            $this->assertStringNotContainsString(route($routeName), $html, "accountant should NOT see {$routeName}");
        }
    }

    public function test_librarian_sees_library_but_not_finance_or_exams(): void
    {
        $html = $this->dashboardHtml('librarian');

        foreach (['students.index', 'books.index', 'leaves.index'] as $routeName) {
            $this->assertStringContainsString(route($routeName), $html, "librarian should see {$routeName}");
        }

        foreach (['invoices.index', 'exams.index', 'attendance.index', 'users.index', 'settings.index'] as $routeName) {
            $this->assertStringNotContainsString(route($routeName), $html, "librarian should NOT see {$routeName}");
        }
    }

    public function test_receptionist_sees_front_desk_modules(): void
    {
        $html = $this->dashboardHtml('receptionist');

        foreach (['students.index', 'transport.routes.index', 'conversations.index', 'leaves.index'] as $routeName) {
            $this->assertStringContainsString(route($routeName), $html, "receptionist should see {$routeName}");
        }

        foreach (['invoices.index', 'exams.index', 'books.index', 'users.index', 'staff.index'] as $routeName) {
            $this->assertStringNotContainsString(route($routeName), $html, "receptionist should NOT see {$routeName}");
        }
    }

    public function test_student_sees_own_portal_and_self_scoped_pages_only(): void
    {
        $html = $this->dashboardHtml('student');

        foreach (['student.attendance', 'invoices.index', 'announcements.index', 'conversations.index', 'homework.index', 'books.index'] as $routeName) {
            $this->assertStringContainsString(route($routeName), $html, "student should see {$routeName}");
        }

        // Staff consoles must stay hidden even though the student holds view-ish permissions
        foreach (['attendance.index', 'exams.index', 'exam-marks.index', 'term-results.index', 'users.index', 'staff.index'] as $routeName) {
            $this->assertStringNotContainsString(route($routeName), $html, "student should NOT see {$routeName}");
        }
    }

    public function test_parent_sees_children_portal_but_no_staff_consoles(): void
    {
        $html = $this->dashboardHtml('parent');

        foreach (['parent.children', 'invoices.index', 'announcements.index', 'conversations.index', 'books.index'] as $routeName) {
            $this->assertStringContainsString(route($routeName), $html, "parent should see {$routeName}");
        }

        foreach (['users.index', 'audit.index', 'fee-structures.index', 'exams.index', 'homework.index'] as $routeName) {
            $this->assertStringNotContainsString(route($routeName), $html, "parent should NOT see {$routeName}");
        }
    }

    // ── Structural: a section header must never render with nothing under it ────
    // Regression guard for the old sidebar's empty "People" header — every group's
    // @if() must match the union of its links' own gates, for every role, always.

    public function test_no_role_ever_renders_an_empty_sidebar_section_header(): void
    {
        foreach (['admin', 'principal', 'teacher', 'accountant', 'librarian', 'receptionist', 'student', 'parent'] as $role) {
            $html = $this->dashboardHtml($role);

            preg_match_all('/class="kia-nav-section"/', $html, $sections, PREG_OFFSET_CAPTURE);
            preg_match_all('/class="kia-nav-item/', $html, $items, PREG_OFFSET_CAPTURE);

            $sectionPositions = array_column($sections[0], 1);
            $itemPositions    = array_column($items[0], 1);

            foreach ($sectionPositions as $i => $sectionPos) {
                $nextSectionPos = $sectionPositions[$i + 1] ?? PHP_INT_MAX;
                $hasLinkInside  = collect($itemPositions)->contains(
                    fn ($itemPos) => $itemPos > $sectionPos && $itemPos < $nextSectionPos
                );

                $this->assertTrue(
                    $hasLinkInside,
                    "Role '{$role}' has an empty sidebar section header (section index {$i}) with no link before the next section or end of nav."
                );
            }
        }
    }
}
