<?php

namespace App\Http\Controllers;

use App\Http\Requests\Semester\StoreSemesterRequest;
use App\Models\AcademicYear;
use App\Models\Semester;

class SemesterController extends Controller
{
    public function store(StoreSemesterRequest $request, AcademicYear $academicYear)
    {
        $academicYear->semesters()->create($request->validated());

        return back()->with('success', __('semester_planning.created'));
    }

    public function destroy(Semester $semester)
    {
        $this->authorize('academic-years.manage');

        $semester->delete();

        return back()->with('success', __('semester_planning.deleted'));
    }
}
