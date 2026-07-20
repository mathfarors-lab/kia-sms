@use('App\Support\Permissions', 'P')
@use('App\Models\TransportRoute')

@php
    // Group visibility: a section header renders only when the user can reach
    // at least one link inside it. Gates mirror each index route's own
    // authorization so a visible link never 403s on click.
    //
    // Every raw permission-string check goes through Permissions::userCan()
    // rather than $u->can() directly: Spatie throws PermissionDoesNotExist —
    // a hard 500 — if a permission row hasn't been provisioned yet (e.g.
    // mid-deploy before the seeder re-runs). Since this sidebar sits in the
    // shared layout, a raw can() here would take down every authenticated
    // page for every user over one missing permission row. A hidden link is
    // a cosmetic downgrade; that isn't. Policy-based @can() checks (viewAny
    // against a model) are unaffected — those never touch the permission
    // table, so they're used as-is.
    $u = auth()->user();
    $can = fn (string $perm) => \App\Support\Permissions::userCan($u, $perm);

    // Teacher's own homeroom section, if any — resolved once here so the
    // "My Timetable" link can point straight at it instead of a picker page.
    // timetables.view only ever grants read-only access to THIS section
    // (enforced again in TimetableController); timetables.manage (admin/
    // principal) already reaches any section via the Classes drill-down.
    $myTimetableSection = ($can(P::TIMETABLES_VIEW) && $u->staff)
        ? \App\Models\Section::where('class_teacher_id', $u->staff->id)->first()
        : null;

    $navAcademic = $can(P::ACADEMIC_YEARS_MANAGE) || $can(P::CLASSES_MANAGE)
                || $can(P::SUBJECTS_MANAGE)        || $can(P::SETTINGS_MANAGE)
                || $can(P::REPORT_COMMENTS_MANAGE) || $can(P::TIMETABLES_MANAGE);
    $navStudents = $can(P::STUDENTS_VIEW) || $can(P::ATTENDANCE_MARK) || $can(P::PROMOTION_MANAGE)
                || $can(P::ADMISSIONS_VIEW) || $can(P::GATE_SCAN) || $myTimetableSection !== null;
    $navExams    = $can(P::EXAMS_MANAGE)  || $can(P::MARKS_ENTRY)     || $can(P::TERM_RESULTS_MANAGE);
    $navFinance  = $can(P::INVOICES_VIEW) || $can(P::FEES_MANAGE)     || $can(P::REPORTS_VIEW);
    $navEngage   = $can(P::ANNOUNCEMENTS_VIEW) || $can(P::MESSAGES_VIEW)
                || $can(P::HOMEWORK_MANAGE)     || $can(P::HOMEWORK_GRADE)
                || $can(P::HOMEWORK_SUBMIT)     || $can(P::HOMEWORK_VIEW)
                || $can(P::FEEDBACK_VIEW);
    $navOps      = $can(P::BOOKS_VIEW) || $u->can('viewAny', TransportRoute::class)
                || $can(P::LEAVES_VIEW) || $can(P::STAFF_VIEW) || $can(P::VISITORS_MANAGE);
    $navSystem   = $can(P::ANALYTICS_VIEW) || $can(P::REPORTS_VIEW) || $can(P::AUDIT_VIEW)
                || $can(P::USERS_MANAGE)   || $can(P::SETTINGS_MANAGE);
@endphp

<nav class="kia-sidebar" id="kiaSidebar">
    {{-- Brand --}}
    <div class="kia-sidebar-brand">
        <div class="kia-sidebar-brand-logo">KIA</div>
        <div class="kia-sidebar-brand-name">
            {{ __('KIA School System') }}
        </div>
    </div>

    <div class="kia-nav">

        {{-- ── Owner ───────────────────────────────────────────────────── --}}
        {{-- No separate "Owner Dashboard" link: the common Dashboard link
             below already routes the owner to owner.dashboard via
             User::dashboardRoute(). This section is for owner-only tools
             that aren't already covered by an existing nav link. --}}
        @role('owner')
        <div class="kia-nav-section">{{ __('nav.owner_section') }}</div>

        <a href="{{ route('owner.branches.index') }}" class="kia-nav-item {{ request()->routeIs('owner.branches.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/>
                <line x1="9" y1="9" x2="9" y2="9.01"/><line x1="9" y1="13" x2="9" y2="13.01"/><line x1="9" y1="17" x2="9" y2="17.01"/>
            </svg>
            {{ __('nav.branches') }}
        </a>
        @endrole

        {{-- Common: Dashboard --}}
        <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="kia-nav-item {{ request()->routeIs('dashboard.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
            {{ __('nav.dashboard') }}
        </a>

        {{-- ── Academic ────────────────────────────────────────────────── --}}
        @if($navAcademic)
        <div class="kia-nav-section">{{ __('nav.academic') }}</div>

        @if($can(P::ACADEMIC_YEARS_MANAGE))
        <a href="{{ route('academic-years.index') }}" class="kia-nav-item {{ request()->routeIs('academic-years.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            {{ __('nav.academic_years') }}
        </a>
        @endif

        @if($can(P::CLASSES_MANAGE))
        <a href="{{ route('classes.index') }}" class="kia-nav-item {{ request()->routeIs('classes.*') || request()->routeIs('sections.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9,22 9,12 15,12 15,22"/>
            </svg>
            {{ __('nav.classes_sections') }}
        </a>
        @endif

        @if($can(P::SUBJECTS_MANAGE))
        <a href="{{ route('subjects.index') }}" class="kia-nav-item {{ request()->routeIs('subjects.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
            </svg>
            {{ __('nav.subjects') }}
        </a>
        @endif

        @if($can(P::SETTINGS_MANAGE))
        <a href="{{ route('grade-scales.index') }}" class="kia-nav-item {{ request()->routeIs('grade-scales.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
            </svg>
            {{ __('nav.grade_scales') }}
        </a>
        @endif

        @if($can(P::REPORT_COMMENTS_MANAGE))
        <a href="{{ route('report-comments.index') }}" class="kia-nav-item {{ request()->routeIs('report-comments.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            {{ __('nav.report_comments') }}
        </a>
        @endif

        {{-- Standalone entry point — additive to the existing Classes &
             Sections → section → Timetable drill-down, not a replacement. --}}
        @if($can(P::TIMETABLES_MANAGE))
        <a href="{{ route('timetables.index') }}" class="kia-nav-item {{ request()->routeIs('timetables.index') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="14" x2="16" y2="14"/>
            </svg>
            {{ __('nav.timetables') }}
        </a>
        @endif
        @endif

        {{-- ── Students ────────────────────────────────────────────────── --}}
        @if($navStudents)
        <div class="kia-nav-section">{{ __('nav.students_group') }}</div>

        @if($can(P::STUDENTS_VIEW))
        <a href="{{ route('students.index') }}" class="kia-nav-item {{ request()->routeIs('students.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            {{ __('nav.students') }}
        </a>
        @endif

        @if($can(P::ADMISSIONS_VIEW))
        <a href="{{ route('admissions.index') }}" class="kia-nav-item {{ request()->routeIs('admissions.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>
            </svg>
            {{ __('nav.admissions') }}
        </a>
        @endif

        @if($can(P::ATTENDANCE_MARK))
        <a href="{{ route('attendance.index') }}" class="kia-nav-item {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="9,11 12,14 22,4"/>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
            {{ __('nav.attendance') }}
        </a>
        @endif

        @if($can(P::GATE_SCAN))
        <a href="{{ route('gate.station') }}" class="kia-nav-item" target="_blank">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="8" width="18" height="12" rx="2"/><path d="M7 8V6a5 5 0 0 1 10 0v2"/>
                <circle cx="12" cy="14" r="1.5"/>
            </svg>
            {{ __('nav.gate_station') }}
        </a>
        @endif

        @if($can(P::PROMOTION_MANAGE))
        <a href="{{ route('promotion.index') }}" class="kia-nav-item {{ request()->routeIs('promotion.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="17,11 12,6 7,11"/><polyline points="17,18 12,13 7,18"/>
            </svg>
            {{ __('nav.promotion') }}
        </a>
        @endif

        @if($myTimetableSection)
        <a href="{{ route('timetable.show', $myTimetableSection) }}" class="kia-nav-item {{ request()->routeIs('timetable.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="14" x2="16" y2="14"/>
            </svg>
            {{ __('nav.my_timetable') }}
        </a>
        @endif
        @endif

        {{-- ── Exams & Results ─────────────────────────────────────────── --}}
        @if($navExams)
        <div class="kia-nav-section">{{ __('nav.exams_results') }}</div>

        @if($can(P::EXAMS_MANAGE) || $can(P::MARKS_ENTRY))
        <a href="{{ route('exams.index') }}" class="kia-nav-item {{ request()->routeIs('exams.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14,2 14,8 20,8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>
            </svg>
            {{ __('nav.exams') }}
        </a>
        @endif

        @if($can(P::MARKS_ENTRY) || $can(P::EXAMS_MANAGE))
        <a href="{{ route('exam-marks.index') }}" class="kia-nav-item {{ request()->routeIs('exam-marks.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/>
            </svg>
            {{ __('nav.exam_marks') }}
        </a>
        @endif

        @if($can(P::TERM_RESULTS_MANAGE) || $can(P::MARKS_ENTRY))
        <a href="{{ route('term-results.index') }}" class="kia-nav-item {{ request()->routeIs('term-results.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            {{ __('nav.term_results') }}
        </a>
        @endif
        @endif

        {{-- ── Finance ─────────────────────────────────────────────────── --}}
        @if($navFinance)
        <div class="kia-nav-section">{{ __('nav.finance') }}</div>

        @if($can(P::INVOICES_VIEW))
        <a href="{{ route('invoices.index') }}" class="kia-nav-item {{ request()->routeIs('invoices.*') || request()->routeIs('payments.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            {{ __('nav.invoices') }}
        </a>
        @endif

        @if($can(P::FEES_MANAGE))
        <a href="{{ route('fee-structures.index') }}" class="kia-nav-item {{ request()->routeIs('fee-structures.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
            {{ __('nav.fee_structures') }}
        </a>

        <a href="{{ route('scholarships.index') }}" class="kia-nav-item {{ request()->routeIs('scholarships.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
            </svg>
            {{ __('nav.scholarships') }}
        </a>
        @endif

        @if($can(P::REPORTS_VIEW))
        <a href="{{ route('finance.dashboard') }}" class="kia-nav-item {{ request()->routeIs('finance.dashboard') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>
            </svg>
            {{ __('nav.finance_dashboard') }}
        </a>

        <a href="{{ route('finance.report') }}" class="kia-nav-item {{ request()->routeIs('finance.report') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14,2 14,8 20,8"/><path d="M8 13h2v4H8zM12 11h2v6h-2zM16 9h2v8h-2z"/>
            </svg>
            {{ __('nav.finance_reports') }}
        </a>
        @endif
        @endif

        {{-- ── Engagement ──────────────────────────────────────────────── --}}
        @if($navEngage)
        <div class="kia-nav-section">{{ __('nav.engagement') }}</div>

        @if($can(P::ANNOUNCEMENTS_VIEW))
        <a href="{{ route('announcements.index') }}" class="kia-nav-item {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>
            </svg>
            {{ __('nav.announcements') }}
        </a>
        @endif

        @if($can(P::MESSAGES_VIEW))
        <a href="{{ route('conversations.index') }}" class="kia-nav-item {{ request()->routeIs('conversations.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            {{ __('nav.messages') }}
        </a>
        @endif

        @if($can(P::FEEDBACK_VIEW))
        <a href="{{ route('feedback.index') }}" class="kia-nav-item {{ request()->routeIs('feedback.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M8 9h8M8 13h5"/><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            {{ __('nav.feedback') }}
        </a>
        @endif

        @if($can(P::HOMEWORK_MANAGE) || $can(P::HOMEWORK_GRADE) || $can(P::HOMEWORK_SUBMIT) || $can(P::HOMEWORK_VIEW))
        <a href="{{ route('homework.index') }}" class="kia-nav-item {{ request()->routeIs('homework.*') || request()->routeIs('homework-submissions.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                <line x1="9" y1="7" x2="15" y2="7"/><line x1="9" y1="11" x2="15" y2="11"/>
            </svg>
            {{ __('nav.homework') }}
        </a>
        @endif
        @endif

        {{-- ── Operations ──────────────────────────────────────────────── --}}
        @if($navOps)
        <div class="kia-nav-section">{{ __('nav.operations') }}</div>

        @if($can(P::BOOKS_VIEW))
        <a href="{{ route('books.index') }}" class="kia-nav-item {{ request()->routeIs('books.*') || request()->routeIs('book-issues.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
            {{ __('nav.library') }}
        </a>
        @endif

        @can('viewAny', TransportRoute::class)
        <a href="{{ route('transport.routes.index') }}" class="kia-nav-item {{ request()->routeIs('transport.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M8 6v6M16 6v6M2 12h19.6M4 6h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-1"/>
                <circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M9 18h6M2 8v8a2 2 0 0 0 2 2h1"/>
            </svg>
            {{ __('nav.transport') }}
        </a>
        @endcan

        @if($can(P::LEAVES_VIEW))
        <a href="{{ route('leaves.index') }}" class="kia-nav-item {{ request()->routeIs('leaves.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/>
            </svg>
            {{ __('nav.leaves') }}
        </a>
        @endif

        @if($can(P::STAFF_VIEW))
        <a href="{{ route('staff.index') }}" class="kia-nav-item {{ request()->routeIs('staff.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            {{ __('nav.staff') }}
        </a>
        @endif

        @if($can(P::VISITORS_MANAGE))
        <a href="{{ route('visitors.index') }}" class="kia-nav-item {{ request()->routeIs('visitors.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-1a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            {{ __('nav.visitors') }}
        </a>
        @endif
        @endif

        {{-- ── System ──────────────────────────────────────────────────── --}}
        @if($navSystem)
        <div class="kia-nav-section">{{ __('nav.system') }}</div>

        @if($can(P::ANALYTICS_VIEW))
        <a href="{{ route('analytics.index') }}" class="kia-nav-item {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
            </svg>
            {{ __('nav.analytics') }}
        </a>
        <a href="{{ route('academic-analytics.index') }}" class="kia-nav-item {{ request()->routeIs('academic-analytics.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M22 10v6M2 10l10-5 10 5-10 5-10-5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
            </svg>
            {{ __('nav.academic_analytics') }}
        </a>
        @endif

        @if($can(P::REPORTS_VIEW))
        <a href="{{ route('reports.index') }}" class="kia-nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14,2 14,8 20,8"/>
            </svg>
            {{ __('nav.reports') }}
        </a>
        @endif

        @if($can(P::AUDIT_VIEW))
        <a href="{{ route('audit.index') }}" class="kia-nav-item {{ request()->routeIs('audit.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            {{ __('nav.audit_log') }}
        </a>
        @endif

        @if($can(P::SETTINGS_MANAGE))
        <a href="{{ route('admin.bakong.failed') }}" class="kia-nav-item {{ request()->routeIs('admin.bakong.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            {{ __('nav.bakong_review') }}
        </a>
        @endif

        @if($can(P::USERS_MANAGE))
        <a href="{{ route('users.index') }}" class="kia-nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            {{ __('nav.users') }}
        </a>
        @endif

        @if($can(P::SETTINGS_MANAGE))
        <a href="{{ route('settings.index') }}" class="kia-nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            {{ __('nav.settings') }}
        </a>
        @endif
        @endif

        {{-- ── Student portal ──────────────────────────────────────────── --}}
        @role('student')
        <div class="kia-nav-section">{{ __('nav.my_school') }}</div>
        <a href="{{ route('student.attendance') }}" class="kia-nav-item {{ request()->routeIs('student.attendance') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="9,11 12,14 22,4"/>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
            {{ __('nav.my_attendance') }}
        </a>
        <a href="{{ route('feedback.index') }}" class="kia-nav-item {{ request()->routeIs('feedback.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M8 9h8M8 13h5"/><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            {{ __('nav.feedback') }}
        </a>
        @endrole

        {{-- ── Parent portal ───────────────────────────────────────────── --}}
        @role('parent')
        <div class="kia-nav-section">{{ __('nav.my_children') }}</div>
        <a href="{{ route('parent.children') }}" class="kia-nav-item {{ request()->routeIs('parent.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
            </svg>
            {{ __('nav.children') }}
        </a>
        <a href="{{ route('feedback.index') }}" class="kia-nav-item {{ request()->routeIs('feedback.*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M8 9h8M8 13h5"/><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            {{ __('nav.feedback') }}
        </a>
        @endrole
    </div>

    {{-- Sidebar footer --}}
    <div class="kia-sidebar-footer">
        <div style="font-size:.72rem;color:rgba(255,255,255,.35);line-height:1.4;">
            {{ __('KIA School System') }} &copy; {{ date('Y') }}<br>
            v1.0.0
        </div>
    </div>
</nav>
