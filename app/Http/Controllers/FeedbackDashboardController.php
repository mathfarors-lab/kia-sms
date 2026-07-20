<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Support\Permissions as P;

class FeedbackDashboardController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function index()
    {
        $this->authorize(P::FEEDBACK_VIEW);

        $byCategory = $this->analytics->feedbackCountsByCategory();
        $volumeByMonth = $this->analytics->feedbackVolumeByMonth();
        $avgResolutionHours = $this->analytics->feedbackAverageResolutionHours();

        $totalOpen = array_sum(array_map(fn ($row) => $row->open + $row->in_progress, $byCategory));
        $totalResolved = array_sum(array_map(fn ($row) => $row->resolved + $row->closed, $byCategory));

        return view('feedback.dashboard', compact(
            'byCategory', 'volumeByMonth', 'avgResolutionHours', 'totalOpen', 'totalResolved'
        ));
    }
}
