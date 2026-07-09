<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Setting;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function redirect(Request $request)
    {
        return redirect()->route($request->user()->dashboardRoute());
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
        return view('dashboard.principal', compact('stats'));
    }

    public function teacher()
    {
        return view('dashboard.teacher');
    }

    public function accountant()
    {
        return view('dashboard.accountant');
    }

    public function librarian()
    {
        return view('dashboard.librarian');
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
