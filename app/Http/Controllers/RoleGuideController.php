<?php

namespace App\Http\Controllers;

use App\Support\Permissions as P;

class RoleGuideController extends Controller
{
    /**
     * Curated, hand-maintained reference data — not introspected from
     * RolePermissionSeeder at runtime. The "scope" annotations (which
     * items are ADDITIONALLY restricted to the viewer's own records
     * beyond the bare permission) reflect actual controller logic, which
     * isn't derivable from the permission grants alone.
     */
    private const ROLES = ['owner', 'admin', 'principal', 'teacher', 'accountant', 'librarian', 'receptionist', 'student', 'parent'];

    public function index()
    {
        $this->authorize(P::USERS_MANAGE);

        // Load the whole arrays with static keys rather than building a
        // per-role key by string interpolation: LocalizationParityTest's
        // bare-literal scanner is regex-based, not a real PHP parser, so an
        // interpolated translation key reads to it as an unresolved literal
        // string and gets flagged as a missing translation.
        $roleNames = __('role_guide.role_names');
        $roleBlurbs = __('role_guide.role_blurbs');

        $roleMeta = collect(self::ROLES)->mapWithKeys(fn ($role) => [
            $role => [
                'label' => $roleNames[$role],
                'blurb' => $roleBlurbs[$role],
            ],
        ])->all();

        return view('role-guide.index', [
            'roles' => self::ROLES,
            'roleMeta' => $roleMeta,
            'sections' => $this->sections(),
        ]);
    }

    private function sections(): array
    {
        return [
            ['id' => 'common', 'title' => null, 'items' => [
                ['label' => __('nav.dashboard'), 'perm' => '(any authenticated user)', 'roles' => ['principal', 'teacher', 'accountant', 'librarian', 'receptionist', 'student', 'parent']],
                ['label' => __('surveys.my_surveys'), 'perm' => '(any authenticated user)', 'roles' => ['principal', 'teacher', 'accountant', 'librarian', 'receptionist', 'student', 'parent']],
                ['label' => __('academic_calendar.nav'), 'perm' => '(any authenticated user)', 'roles' => ['principal', 'teacher', 'accountant', 'librarian', 'receptionist', 'student', 'parent']],
            ]],
            ['id' => 'academic', 'title' => __('nav.academic'), 'items' => [
                ['label' => __('nav.academic_years'), 'perm' => 'academic-years.manage', 'roles' => ['principal']],
                ['label' => __('nav.classes_sections'), 'perm' => 'classes.manage', 'roles' => ['principal']],
                ['label' => __('nav.subjects'), 'perm' => 'subjects.manage', 'roles' => ['principal']],
                ['label' => __('nav.grade_scales'), 'perm' => 'settings.manage', 'roles' => ['principal']],
                ['label' => __('curriculum.nav'), 'perm' => 'curriculum.view', 'roles' => ['principal', 'teacher'], 'scope' => ['teacher' => 'full']],
                ['label' => __('nav.report_comments'), 'perm' => 'report-comments.manage', 'roles' => ['principal']],
                ['label' => __('nav.timetables'), 'perm' => 'timetables.manage', 'roles' => []],
            ]],
            ['id' => 'students', 'title' => __('nav.students_group'), 'items' => [
                ['label' => __('nav.students'), 'perm' => 'students.view', 'roles' => ['principal', 'teacher', 'accountant', 'librarian', 'receptionist'], 'scope' => ['teacher' => 'scoped']],
                ['label' => __('nav.admissions'), 'perm' => 'admissions.view', 'roles' => ['principal', 'receptionist']],
                ['label' => __('nav.attendance'), 'perm' => 'attendance.mark', 'roles' => ['principal', 'teacher'], 'scope' => ['teacher' => 'scoped']],
                ['label' => __('nav.gate_station'), 'perm' => 'gate.scan', 'roles' => ['receptionist']],
                ['label' => __('nav.promotion'), 'perm' => 'promotion.manage', 'roles' => ['principal']],
                ['label' => __('nav.my_timetable'), 'perm' => 'timetables.view + own section', 'roles' => ['teacher'], 'scope' => ['teacher' => 'scoped']],
            ]],
            ['id' => 'exams', 'title' => __('nav.exams_results'), 'items' => [
                ['label' => __('nav.exams'), 'perm' => 'exams.manage / marks.entry', 'roles' => ['principal', 'teacher']],
                ['label' => __('nav.exam_marks'), 'perm' => 'marks.entry / exams.manage', 'roles' => ['principal', 'teacher'], 'scope' => ['teacher' => 'scoped']],
                ['label' => __('nav.term_results'), 'perm' => 'term-results.manage / marks.entry', 'roles' => ['principal', 'teacher']],
                ['label' => __('nav.school_ranking'), 'perm' => 'term-results.manage', 'roles' => ['principal']],
                ['label' => __('nav.term_ranking'), 'perm' => 'term-results.manage', 'roles' => ['principal']],
            ]],
            ['id' => 'finance', 'title' => __('nav.finance'), 'items' => [
                ['label' => __('nav.invoices'), 'perm' => 'invoices.view', 'roles' => ['principal', 'accountant', 'student', 'parent'], 'scope' => ['student' => 'scoped', 'parent' => 'scoped']],
                ['label' => __('nav.fee_structures'), 'perm' => 'fees.manage', 'roles' => ['principal', 'accountant']],
                ['label' => __('nav.scholarships'), 'perm' => 'fees.manage', 'roles' => ['principal', 'accountant']],
                ['label' => __('nav.finance_dashboard'), 'perm' => 'reports.view', 'roles' => ['principal', 'accountant']],
                ['label' => __('nav.finance_reports'), 'perm' => 'reports.view', 'roles' => ['principal', 'accountant']],
            ]],
            ['id' => 'engage', 'title' => __('nav.engagement'), 'items' => [
                ['label' => __('nav.announcements'), 'perm' => 'announcements.view', 'roles' => ['principal', 'teacher', 'student', 'parent'], 'scope' => ['teacher' => 'scoped (audience)']],
                ['label' => __('nav.messages'), 'perm' => 'messages.view', 'roles' => ['principal', 'teacher', 'accountant', 'receptionist', 'student', 'parent'], 'scope' => ['teacher' => 'scoped', 'student' => 'scoped', 'parent' => 'scoped']],
                ['label' => __('nav.feedback'), 'perm' => 'feedback.view', 'roles' => ['principal']],
                ['label' => __('surveys.title'), 'perm' => 'surveys.view', 'roles' => ['principal']],
                ['label' => __('nav.homework'), 'perm' => 'homework.manage/grade/submit/view', 'roles' => ['principal', 'teacher', 'student'], 'scope' => ['teacher' => 'scoped', 'student' => 'scoped']],
            ]],
            ['id' => 'ops', 'title' => __('nav.operations'), 'items' => [
                ['label' => __('nav.library'), 'perm' => 'books.view', 'roles' => ['principal', 'teacher', 'librarian', 'student', 'parent'], 'scope' => ['teacher' => 'scoped (issue history)', 'student' => 'scoped (issue history)']],
                ['label' => __('nav.transport'), 'perm' => 'transport.view', 'roles' => ['principal', 'receptionist']],
                ['label' => __('nav.leaves'), 'perm' => 'leaves.view', 'roles' => ['principal', 'teacher', 'accountant', 'librarian', 'receptionist'], 'scope' => ['principal' => 'full (reviewer)', 'teacher' => 'scoped', 'accountant' => 'scoped', 'librarian' => 'scoped', 'receptionist' => 'scoped']],
                ['label' => __('nav.staff'), 'perm' => 'staff.view', 'roles' => ['principal']],
                ['label' => __('staff_evaluations.my_evaluations'), 'perm' => '(has a Staff record)', 'roles' => ['principal', 'teacher', 'accountant', 'librarian', 'receptionist'], 'scope' => ['teacher' => 'scoped', 'accountant' => 'scoped', 'librarian' => 'scoped', 'receptionist' => 'scoped']],
                ['label' => __('school_documents.nav'), 'perm' => 'documents.view', 'roles' => ['principal', 'teacher', 'accountant', 'librarian', 'receptionist'], 'scope' => ['teacher' => 'scoped']],
            ]],
            ['id' => 'system', 'title' => __('nav.system'), 'items' => [
                ['label' => __('nav.analytics'), 'perm' => 'analytics.view', 'roles' => ['principal']],
                ['label' => __('nav.academic_analytics'), 'perm' => 'analytics.view', 'roles' => ['principal']],
                ['label' => __('nav.reports'), 'perm' => 'reports.view', 'roles' => ['principal', 'accountant']],
                ['label' => __('nav.audit_log'), 'perm' => 'audit.view', 'roles' => ['principal']],
                ['label' => __('nav.bakong_review'), 'perm' => 'settings.manage', 'roles' => ['principal']],
                ['label' => __('nav.users'), 'perm' => 'users.manage', 'roles' => []],
                ['label' => __('nav.settings'), 'perm' => 'settings.manage', 'roles' => ['principal']],
            ]],
            ['id' => 'student-portal', 'title' => __('nav.my_school') . ' (' . __('role_guide.role_names.student') . ' only)', 'items' => [
                ['label' => __('nav.my_attendance'), 'perm' => '(role: student, own record)', 'roles' => ['student'], 'scope' => ['student' => 'scoped']],
                ['label' => __('nav.feedback'), 'perm' => '(role: student)', 'roles' => ['student']],
            ]],
            ['id' => 'parent-portal', 'title' => __('nav.my_children') . ' (' . __('role_guide.role_names.parent') . ' only)', 'items' => [
                ['label' => __('nav.children'), 'perm' => '(role: parent, own wards)', 'roles' => ['parent'], 'scope' => ['parent' => 'scoped']],
                ['label' => __('nav.feedback'), 'perm' => '(role: parent)', 'roles' => ['parent']],
            ]],
            ['id' => 'owner-only', 'title' => __('role_guide.role_names.owner'), 'items' => [
                ['label' => __('nav.branches'), 'perm' => '(role: owner)', 'roles' => []],
            ]],
        ];
    }
}
