<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Services\PromotionService;
use App\Support\Permissions as P;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function __construct(private PromotionService $service) {}

    /** Step 1 — year selection form. */
    public function index()
    {
        $this->authorize(P::PROMOTION_MANAGE);

        $years    = AcademicYear::orderByDesc('start_date')->get();
        $fromYear = AcademicYear::where('is_active', true)->first();

        return view('promotion.index', compact('years', 'fromYear'));
    }

    /** Step 2 — dry-run preview (nothing is written). */
    public function preview(Request $request)
    {
        $this->authorize(P::PROMOTION_MANAGE);

        $request->validate([
            'from_year_id' => ['required', 'exists:academic_years,id'],
            'to_year_id'   => ['required', 'exists:academic_years,id', 'different:from_year_id'],
        ]);

        $fromYear = AcademicYear::findOrFail($request->from_year_id);
        $toYear   = AcademicYear::findOrFail($request->to_year_id);

        $preview = $this->service->preview($fromYear, $toYear);

        return view('promotion.preview', compact('fromYear', 'toYear', 'preview'));
    }

    /** Step 3 — execute (writes inside a single transaction). */
    public function execute(Request $request)
    {
        $this->authorize(P::PROMOTION_MANAGE);

        $request->validate([
            'from_year_id'      => ['required', 'exists:academic_years,id'],
            'to_year_id'        => ['required', 'exists:academic_years,id', 'different:from_year_id'],
            'activate_new_year' => ['boolean'],
            'overrides'         => ['array'],
            'overrides.*'       => ['in:promote,retain,graduate,withdraw'],
        ]);

        $fromYear = AcademicYear::findOrFail($request->from_year_id);
        $toYear   = AcademicYear::findOrFail($request->to_year_id);

        $counts = $this->service->execute(
            $fromYear,
            $toYear,
            $request->input('overrides', []),
            $request->boolean('activate_new_year')
        );

        return view('promotion.result', compact('fromYear', 'toYear', 'counts',));
    }
}
