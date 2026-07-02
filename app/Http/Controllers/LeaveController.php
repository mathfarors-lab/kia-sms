<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Services\LeaveService;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LeaveController extends Controller
{
    public function __construct(private LeaveService $service) {}

    public function index()
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['admin', 'principal'])) {
            $leaves = Leave::with('user')->latest()->paginate(25);
        } else {
            // Staff see only their own
            Gate::authorize(Permissions::LEAVES_VIEW);
            $leaves = Leave::where('user_id', $user->id)->latest()->paginate(25);
        }

        return view('leaves.index', compact('leaves'));
    }

    public function create()
    {
        Gate::authorize(Permissions::LEAVES_SUBMIT);
        return view('leaves.create');
    }

    public function store(Request $request)
    {
        Gate::authorize(Permissions::LEAVES_SUBMIT);

        $data = $request->validate([
            'type'       => ['required', 'in:sick,annual,unpaid,other'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'reason'     => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->submit(auth()->user(), $data);

        return redirect()->route('leaves.index')->with('success', __('leave.submitted'));
    }

    public function approve(Leave $leave)
    {
        Gate::authorize(Permissions::LEAVES_MANAGE);

        $this->service->approve($leave, auth()->user());

        return back()->with('success', __('leave.approved'));
    }

    public function reject(Request $request, Leave $leave)
    {
        Gate::authorize(Permissions::LEAVES_MANAGE);

        $data = $request->validate([
            'reviewer_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->reject($leave, auth()->user(), $data['reviewer_note'] ?? null);

        return back()->with('success', __('leave.rejected'));
    }
}
