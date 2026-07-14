<?php

namespace App\Http\Controllers;

use App\Http\Requests\VisitorLog\StoreVisitorLogRequest;
use App\Models\Staff;
use App\Models\VisitorLog;
use Illuminate\Http\Request;

class VisitorLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('visitors.manage');

        $date = $request->date ?: today()->toDateString();

        $visitors = VisitorLog::with(['hostStaff.user'])
            ->whereDate('time_in', $date)
            ->orderByDesc('time_in')
            ->paginate(20)
            ->withQueryString();

        $staff = Staff::with('user')->get();
        $currentlyOnSite = VisitorLog::whereNull('time_out')->whereDate('time_in', today())->count();

        return view('visitors.index', compact('visitors', 'staff', 'date', 'currentlyOnSite'));
    }

    public function store(StoreVisitorLogRequest $request)
    {
        VisitorLog::create([
            ...$request->validated(),
            'time_in'     => now(),
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()->route('visitors.index')->with('success', __('visitors.checked_in'));
    }

    public function checkOut(VisitorLog $visitor)
    {
        $this->authorize('visitors.manage');

        if (!$visitor->isCheckedOut()) {
            $visitor->update(['time_out' => now()]);
        }

        return back()->with('success', __('visitors.checked_out'));
    }
}
