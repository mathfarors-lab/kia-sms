<?php

namespace App\Http\Controllers;

use App\Exports\StaffExport;
use App\Models\Staff;
use App\Services\StaffService;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function __construct(private StaffService $service) {}

    public function index(Request $request)
    {
        $this->authorize('staff.view');

        $staff = $this->filteredQuery($request)->paginate(20)->withQueryString();
        $departments = Staff::distinct()->pluck('department')->filter()->sort()->values();

        return view('staff.index', compact('staff', 'departments'));
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('staff.view');

        $staff = $this->filteredQuery($request)->get();

        return Excel::download(new StaffExport($staff), 'staff-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('staff.view');

        $staff = $this->filteredQuery($request)->get();

        $pdf = Pdf::loadView('pdf.staff-list', compact('staff'))
            ->setPaper('a4', 'landscape')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download('staff-' . now()->format('Y-m-d') . '.pdf');
    }

    private function filteredQuery(Request $request)
    {
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

        return $query;
    }

    public function create()
    {
        $this->authorize('staff.create');
        $roles = Role::whereIn('name', ['teacher', 'accountant', 'librarian', 'receptionist', 'principal'])->pluck('name');
        return view('staff.create', compact('roles'));
    }

    public function store(StoreStaffRequest $request)
    {
        $staff = $this->service->store(
            $request->safe()->except('photo'),
            $request->file('photo')
        );
        return redirect()->route('staff.show', $staff)
                         ->with('success', __('Staff member created successfully.'));
    }

    public function show(Staff $staff)
    {
        $this->authorize('staff.view');
        $staff->load('user', 'issuedDocuments');
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
        $this->service->update(
            $staff,
            $request->safe()->except('photo'),
            $request->file('photo')
        );
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
