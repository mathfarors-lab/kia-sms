<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\StoreStaffQualificationRequest;
use App\Models\Staff;
use App\Models\StaffQualification;

class StaffQualificationController extends Controller
{
    public function store(StoreStaffQualificationRequest $request, Staff $staff)
    {
        $this->authorize('staff.edit');

        StaffQualification::create(array_merge($request->validated(), ['staff_id' => $staff->id]));

        return back()->with('success', __('staff_qualifications.added'));
    }

    public function destroy(StaffQualification $qualification)
    {
        $this->authorize('staff.edit');

        $qualification->delete();

        return back()->with('success', __('staff_qualifications.deleted'));
    }
}
