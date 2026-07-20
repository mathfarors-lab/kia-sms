<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\StoreStaffEvaluationRequest;
use App\Models\Staff;
use App\Models\StaffEvaluation;
use App\Support\Permissions as P;
use Illuminate\Support\Facades\Auth;

class StaffEvaluationController extends Controller
{
    /**
     * Not gated by staff.view — that permission excludes teacher today, and
     * a teacher must be able to reach their own evaluations. Same self-view
     * carve-out shape as TimetableController::teacherSchedule().
     */
    public function index(Staff $staff)
    {
        $user = Auth::user();

        if ($user->can(P::STAFF_EVALUATIONS_MANAGE)) {
            $evaluations = $staff->evaluations;
        } elseif ($user->id === $staff->user_id) {
            $evaluations = $staff->evaluations()->where('status', StaffEvaluation::STATUS_FINALIZED)->get();
        } else {
            abort(403);
        }

        return view('staff-evaluations.index', compact('staff', 'evaluations'));
    }

    public function create(Staff $staff)
    {
        $this->authorize('staff-evaluations.manage');

        return view('staff-evaluations.create', compact('staff'));
    }

    public function store(StoreStaffEvaluationRequest $request, Staff $staff)
    {
        $evaluation = StaffEvaluation::create(array_merge($request->validated(), [
            'staff_id' => $staff->id,
            'evaluated_by' => $request->user()->id,
            'status' => StaffEvaluation::STATUS_DRAFT,
        ]));

        return redirect()->route('staff-evaluations.show', $evaluation)->with('success', __('staff_evaluations.created'));
    }

    public function show(StaffEvaluation $evaluation)
    {
        $this->authorizeView($evaluation);

        return view('staff-evaluations.show', compact('evaluation'));
    }

    public function edit(StaffEvaluation $evaluation)
    {
        $this->authorize('staff-evaluations.manage');
        abort_unless($evaluation->status === StaffEvaluation::STATUS_DRAFT, 403, __('staff_evaluations.locked_once_finalized'));

        return view('staff-evaluations.edit', compact('evaluation'));
    }

    public function update(StoreStaffEvaluationRequest $request, StaffEvaluation $evaluation)
    {
        abort_unless($evaluation->status === StaffEvaluation::STATUS_DRAFT, 403, __('staff_evaluations.locked_once_finalized'));

        $evaluation->update($request->validated());

        return redirect()->route('staff-evaluations.show', $evaluation)->with('success', __('staff_evaluations.updated'));
    }

    public function finalize(StaffEvaluation $evaluation)
    {
        $this->authorize('staff-evaluations.manage');
        abort_unless($evaluation->status === StaffEvaluation::STATUS_DRAFT, 403);

        $evaluation->update(['status' => StaffEvaluation::STATUS_FINALIZED, 'finalized_at' => now()]);

        return redirect()->route('staff-evaluations.show', $evaluation)->with('success', __('staff_evaluations.finalized'));
    }

    private function authorizeView(StaffEvaluation $evaluation): void
    {
        $user = Auth::user();

        if ($user->can(P::STAFF_EVALUATIONS_MANAGE)) {
            return;
        }

        if ($user->id === $evaluation->staff->user_id && $evaluation->isFinalized()) {
            return;
        }

        abort(403);
    }
}
