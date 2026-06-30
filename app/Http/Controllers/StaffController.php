<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Services\StaffService;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function __construct(private StaffService $service) {}

    public function index(Request $request)
    {
        $this->authorize('staff.view');

        $query = Staff::with('user')->orderBy('id');

        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('staff_code', 'like', "%{$search}%")
              ->orWhere('department', 'like', "%{$search}%");
        }

        if ($dept = $request->input('department')) {
            $query->where('department', $dept);
        }

        $staff = $query->paginate(20)->withQueryString();
        $departments = Staff::distinct()->pluck('department')->filter()->sort()->values();

        return view('staff.index', compact('staff', 'departments'));
    }

    public function create()
    {
        $this->authorize('staff.create');
        $roles = Role::whereIn('name', ['teacher', 'accountant', 'librarian', 'receptionist', 'principal'])->pluck('name');
        return view('staff.create', compact('roles'));
    }

    public function store(StoreStaffRequest $request)
    {
        $staff = $this->service->store($request->validated());
        return redirect()->route('staff.show', $staff)
                         ->with('success', __('Staff member created successfully.'));
    }

    public function show(Staff $staff)
    {
        $this->authorize('staff.view');
        $staff->load('user');
        return view('staff.show', compact('staff'));
    }

    public function edit(Staff $staff)
    {
        $this->authorize('staff.edit');
        $roles = Role::whereIn('name', ['teacher', 'accountant', 'librarian', 'receptionist', 'principal'])->pluck('name');
        return view('staff.edit', compact('staff', 'roles'));
    }

    public function update(UpdateStaffRequest $request, Staff $staff)
    {
        $this->service->update($staff, $request->validated());
        return redirect()->route('staff.show', $staff)
                         ->with('success', __('Staff updated successfully.'));
    }

    public function destroy(Staff $staff)
    {
        $this->authorize('staff.delete');
        $staff->delete();
        return redirect()->route('staff.index')->with('success', __('Staff member removed.'));
    }
}
