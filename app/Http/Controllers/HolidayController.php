<?php

namespace App\Http\Controllers;

use App\Http\Requests\Holiday\StoreHolidayRequest;
use App\Models\Holiday;

class HolidayController extends Controller
{
    public function index()
    {
        $this->authorize('academic-calendar.manage');
        $holidays = Holiday::orderByDesc('start_date')->paginate(20);

        return view('holidays.index', compact('holidays'));
    }

    public function create()
    {
        $this->authorize('academic-calendar.manage');

        return view('holidays.create');
    }

    public function store(StoreHolidayRequest $request)
    {
        Holiday::create($request->validated());

        return redirect()->route('holidays.index')->with('success', __('academic_calendar.holiday_created'));
    }

    public function edit(Holiday $holiday)
    {
        $this->authorize('academic-calendar.manage');

        return view('holidays.edit', compact('holiday'));
    }

    public function update(StoreHolidayRequest $request, Holiday $holiday)
    {
        $holiday->update($request->validated());

        return redirect()->route('holidays.index')->with('success', __('academic_calendar.holiday_updated'));
    }

    public function destroy(Holiday $holiday)
    {
        $this->authorize('academic-calendar.manage');

        $holiday->delete();

        return redirect()->route('holidays.index')->with('success', __('academic_calendar.holiday_deleted'));
    }
}
