<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolClass\StoreSchoolClassRequest;
use App\Models\SchoolClass;

class SchoolClassController extends Controller
{
    public function index()
    {
        $this->authorize('classes.manage');
        $classes = SchoolClass::withCount('sections')
            ->withCount(['sections as students_count' => function ($q) {
                $q->join('student_section', 'student_section.section_id', '=', 'sections.id');
            }])
            ->latest()->paginate(20);
        return view('classes.index', compact('classes'));
    }

    public function create()
    {
        $this->authorize('classes.manage');
        $classes = SchoolClass::orderBy('name')->get();
        return view('classes.create', compact('classes'));
    }

    public function store(StoreSchoolClassRequest $request)
    {
        $this->authorize('classes.manage');
        SchoolClass::create($request->validated());
        return redirect()->route('classes.index')->with('success', 'Class created.');
    }

    public function show(SchoolClass $class)
    {
        $this->authorize('classes.manage');
        $class->load(['sections.classTeacher.user', 'subjects']);

        $class->sections->each(function ($section) {
            $section->students_count = \Illuminate\Support\Facades\DB::table('student_section')
                ->where('section_id', $section->id)
                ->count();
        });

        return view('classes.show', compact('class'));
    }

    public function edit(SchoolClass $class)
    {
        $this->authorize('classes.manage');
        $classes = SchoolClass::where('id', '!=', $class->id)->orderBy('name')->get();
        return view('classes.edit', compact('class', 'classes'));
    }

    public function update(StoreSchoolClassRequest $request, SchoolClass $class)
    {
        $this->authorize('classes.manage');
        $class->update($request->validated());
        return redirect()->route('classes.index')->with('success', 'Class updated.');
    }

    public function destroy(SchoolClass $class)
    {
        $this->authorize('classes.manage');
        $class->delete();
        return redirect()->route('classes.index')->with('success', 'Class deleted.');
    }
}
