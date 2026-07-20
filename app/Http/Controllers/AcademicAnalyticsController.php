<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Services\AnalyticsService;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AcademicAnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $service) {}

    public function index(Request $request)
    {
        Gate::authorize(Permissions::ANALYTICS_VIEW);

        $year = AcademicYear::find($request->integer('year_id'))
            ?? AcademicYear::where('is_active', true)->firstOrFail();

        $years             = AcademicYear::orderByDesc('start_date')->get();
        $passRate          = $this->service->academicPassRate($year);
        $bySection         = $this->service->academicAverageBySection($year);
        $subjectAverages   = $this->service->academicSubjectAverages($year);
        $gradeDistribution = $this->service->academicGradeDistribution($year);

        return view('academic-analytics.index', compact(
            'year', 'years', 'passRate', 'bySection', 'subjectAverages', 'gradeDistribution'
        ));
    }
}
