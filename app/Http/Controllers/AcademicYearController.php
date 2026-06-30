<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcademicYear\StoreAcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index()
    {
        $this->authorize('academic-years.manage');
        $years = AcademicYear::latest()->paginate(20);
        return view('academic-years.index', compact('years'));
    }

    public function create()
    {
        $this->authorize('academic-years.manage');
        return view('academic-years.create');
    }

    public function store(StoreAcademicYearRequest $request)
    {
        $this->authorize('academic-years.manage');

        $data = $request->validated();

        if (!empty($data['is_active'])) {
            AcademicYear::query()->update(['is_active' => false]);
        }

        AcademicYear::create($data);

        return redirect()->route('academic-years.index')
                         ->with('success', 'Academic year created.');
    }

    public function edit(AcademicYear $academicYear)
    {
        $this->authorize('academic-years.manage');
        return view('academic-years.edit', compact('academicYear'));
    }

    public function update(StoreAcademicYearRequest $request, AcademicYear $academicYear)
    {
        $this->authorize('academic-years.manage');

        $data = $request->validated();

        if (!empty($data['is_active'])) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);
        }

        $academicYear->update($data);

        return redirect()->route('academic-years.index')
                         ->with('success', 'Academic year updated.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        $this->authorize('academic-years.manage');
        $academicYear->delete();
        return redirect()->route('academic-years.index')
                         ->with('success', 'Academic year deleted.');
    }
}
