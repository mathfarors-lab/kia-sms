<?php

namespace App\Http\Controllers;

use App\Http\Requests\Section\StoreSectionRequest;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;

class SectionController extends Controller
{
    public function index(SchoolClass $class)
    {
        $this->authorize('sections.manage');
        $sections = $class->sections()->with('classTeacher.user')->get();
        return view('sections.index', compact('class', 'sections'));
    }

    public function create(SchoolClass $class)
    {
        $this->authorize('sections.manage');
        $staffList = Staff::with('user')->get();
        return view('sections.create', compact('class', 'staffList'));
    }

    public function store(StoreSectionRequest $request, SchoolClass $class)
    {
        $this->authorize('sections.manage');
        $class->sections()->create($request->validated());
        return redirect()->route('classes.sections.index', $class)
                         ->with('success', 'Section created.');
    }

    public function edit(SchoolClass $class, Section $section)
    {
        $this->authorize('sections.manage');
        $staffList = Staff::with('user')->get();
        return view('sections.edit', compact('class', 'section', 'staffList'));
    }

    public function update(StoreSectionRequest $request, SchoolClass $class, Section $section)
    {
        $this->authorize('sections.manage');
        $section->update($request->validated());
        return redirect()->route('classes.sections.index', $class)
                         ->with('success', 'Section updated.');
    }

    public function destroy(SchoolClass $class, Section $section)
    {
        $this->authorize('sections.manage');
        $section->delete();
        return redirect()->route('classes.sections.index', $class)
                         ->with('success', 'Section deleted.');
    }
}
