<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Services\AnalyticsService;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $service) {}

    public function index(Request $request)
    {
        Gate::authorize(Permissions::ANALYTICS_VIEW);

        $year = AcademicYear::find($request->integer('year_id'))
            ?? AcademicYear::where('is_active', true)->firstOrFail();

        $years   = AcademicYear::orderByDesc('start_date')->get();
        $stats   = $this->service->overview($year);
        $byMonth = $this->service->attendanceByMonth($year);
        $feeByMonth = $this->service->feeByMonth($year);

        return view('analytics.index', compact('year', 'years', 'stats', 'byMonth', 'feeByMonth'));
    }
}
