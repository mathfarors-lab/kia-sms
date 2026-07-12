<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AdmissionApplication;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\HomeworkSubmission;
use App\Models\Section;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Setting;
use App\Models\Timetable;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function redirect(Request $request)
    {
        return redirect()->route($request->user()->dashboardRoute());
    }

    /**
     * Consolidated cross-branch comparison. The one dashboard that
     * deliberately reads across every branch — see
     * AnalyticsService::perBranchOverview() for why that's safe here and
     * nowhere else. Route itself is owner-only (role:owner middleware).
     */
    public function owner()
    {
        $year = AcademicYear::where('is_active', true)->first();

        $branchRows = $year ? $this->analytics->perBranchOverview($year) : [];

        // Student/Staff carry the Eloquent BranchScope, which would otherwise
        // silently count only the owner's currently-switched branch — these
        // grand totals must deliberately cross every branch, same reasoning
        // as perBranchOverview().
        $totals = [
            'branches'    => Branch::count(),
            'active'      => Branch::where('is_active', true)->count(),
            'students'    => Student::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)->count(),
            'staff'       => Staff::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)->count(),
            'revenue'     => array_sum(array_column($branchRows, 'revenue')),
            'outstanding' => array_sum(array_column($branchRows, 'outstanding')),
        ];

        return view('dashboard.owner', compact('branchRows', 'totals', 'year'));
    }

    public function admin()
    {
        $year = AcademicYear::where('is_active', true)->first();

        $stats = [
            'total_students'   => Student::count(),
            'total_staff'      => Staff::count(),
            'enrolled'         => Student::where('status', 'enrolled')->count(),
            'revenue_month'    => $this->analytics->revenueThisMonth(),
            'attendance_today' => $year ? $this->analytics->attendanceRateToday($year) : null,
        ];

        // Not cached — an activity feed that's minutes stale defeats its own purpose.
        $recentActivity = Activity::with('causer')->latest()->limit(8)->get();

        return view('dashboard.admin', compact('stats', 'recentActivity'));
    }

    public function principal()
    {
        $stats = [
            'total_students' => Student::count(),
            'enrolled'       => Student::where('status', 'enrolled')->count(),
            'total_staff'    => Staff::count(),
        ];

        $pendingAdmissions = AdmissionApplication::whereIn('status', ['enquiry', 'applied', 'under_review'])
            ->latest()
            ->limit(5)
            ->get();
        $pendingAdmissionsCount = AdmissionApplication::whereIn('status', ['enquiry', 'applied', 'under_review'])->count();

        return view('dashboard.principal', compact('stats', 'pendingAdmissions', 'pendingAdmissionsCount'));
    }

    public function teacher()
    {
        $staff = auth()->user()->staff;

        if (!$staff) {
            return view('dashboard.teacher', [
                'sections' => collect(), 'todayTimetable' => collect(), 'pendingGradeCount' => 0,
            ]);
        }

        // Own sections = homeroom (class_teacher_id) UNION sections of classes
        // this teacher is assigned a subject in (class_subject.teacher_id).
        $homeroomSectionIds = $staff->homeroomSections()->pluck('id');
        $taughtClassIds     = DB::table('class_subject')->where('teacher_id', $staff->id)->pluck('school_class_id');
        $taughtSectionIds   = Section::whereIn('school_class_id', $taughtClassIds)->pluck('id');
        $sectionIds         = $homeroomSectionIds->merge($taughtSectionIds)->unique();

        $activeYear = AcademicYear::where('is_active', true)->first();
        $markedTodaySectionIds = Attendance::whereIn('section_id', $sectionIds)
            ->whereDate('date', today())
            ->distinct()
            ->pluck('section_id');

        $sections = Section::whereIn('id', $sectionIds)
            ->with('schoolClass')
            ->withCount(['students' => function ($q) use ($activeYear) {
                // wherePivot() doesn't compile correctly inside a withCount
                // closure — it needs the pivot table's real column name here.
                if ($activeYear) {
                    $q->where('student_section.academic_year_id', $activeYear->id);
                }
            }])
            ->get()
            ->map(function ($section) use ($markedTodaySectionIds) {
                $section->attendance_marked_today = $markedTodaySectionIds->contains($section->id);
                return $section;
            });

        $todayTimetable = Timetable::where('teacher_id', $staff->id)
            ->where('day', strtolower(now()->format('l')))
            ->with(['subject', 'section.schoolClass'])
            ->orderBy('period')
            ->get();

        $pendingGradeCount = HomeworkSubmission::whereNull('grade')
            ->whereHas('homework', fn ($q) => $q->where('teacher_id', $staff->id))
            ->count();

        return view('dashboard.teacher', compact('sections', 'todayTimetable', 'pendingGradeCount'));
    }

    public function accountant()
    {
        $stats = [
            'collected_month' => $this->analytics->revenueThisMonth(),
            'outstanding'     => $this->analytics->outstandingTotal(),
            'overdue_count'   => $this->analytics->overdueInvoiceCount(),
        ];

        return view('dashboard.accountant', compact('stats'));
    }

    public function librarian()
    {
        $stats = [
            'total_books'      => $this->analytics->totalBooksCount(),
            'currently_issued' => $this->analytics->booksCurrentlyIssuedCount(),
            'overdue_count'    => $this->analytics->overdueBooksCount(),
        ];

        return view('dashboard.librarian', compact('stats'));
    }

    public function receptionist()
    {
        $stats = [
            'total_students' => Student::count(),
            'enrolled'       => Student::where('status', 'enrolled')->count(),
        ];
        return view('dashboard.receptionist', compact('stats'));
    }

    public function student()
    {
        $student = auth()->user()->student;
        return view('dashboard.student', compact('student'));
    }

    public function parent()
    {
        $children = auth()->user()->wards;
        return view('dashboard.parent', compact('children'));
    }
}
