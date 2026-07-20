<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\StoreStaffDevelopmentLogRequest;
use App\Models\Staff;
use App\Models\StaffDevelopmentLog;

class StaffDevelopmentLogController extends Controller
{
    public function store(StoreStaffDevelopmentLogRequest $request, Staff $staff)
    {
        $this->authorize('staff.edit');

        StaffDevelopmentLog::create(array_merge($request->validated(), [
            'staff_id' => $staff->id,
            'added_by' => $request->user()->id,
        ]));

        return back()->with('success', __('staff_development.added'));
    }

    public function destroy(StaffDevelopmentLog $log)
    {
        $this->authorize('staff.edit');

        $log->delete();

        return back()->with('success', __('staff_development.deleted'));
    }
}
