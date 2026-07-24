<?php

namespace App\Http\Controllers;

use App\Http\Requests\Section\StoreSectionRequest;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\TermResult;
use Illuminate\Support\Facades\DB;

class SectionController extends Controller
{
    public function index(SchoolClass $class)
    {
        $this->authorize('sections.manage');
        $sections = $class->sections()->with('classTeacher.user')->get();
        return view('sections.index', compact('class', 'sections'));
    }

    /**
     * The actual class roster: who's enrolled, their attendance rate this
     * year, and their latest published annual result — the "which students,
     * what grade, how's their attendance" view a class-level page can't show
     * on its own since a class can have several sections.
     */
    public function show(Section $section)
    {
        $this->authorize('sections.manage');
        $section->load('schoolClass', 'classTeacher.user');
        $class = $section->schoolClass;

        $activeYear = AcademicYear::where('is_active', true)->first();

        $students = $activeYear
            ? $section->students()->wherePivot('academic_year_id', $activeYear->id)->orderBy('name_en')->get()
            : collect();

        $attendance = collect();
        $results    = collect();

        if ($activeYear && $students->isNotEmpty()) {
            $studentIds = $students->pluck('id');

            $attendance = DB::table('attendances')
                ->where('section_id', $section->id)
                ->whereIn('student_id', $studentIds)
                ->selectRaw("
                    student_id,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                ")
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');

            $results = TermResult::where('academic_year_id', $activeYear->id)
                ->whereNull('semester')
                ->where('section_id', $section->id)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->keyBy('student_id');
        }

        return view('sections.show', compact('class', 'section', 'students', 'activeYear', 'attendance', 'results'));
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

    public function edit(Section $section)
    {
        $this->authorize('sections.manage');
        $class     = $section->schoolClass;
        $staffList = Staff::with('user')->get();
        return view('sections.edit', compact('class', 'section', 'staffList'));
    }

    public function update(StoreSectionRequest $request, Section $section)
    {
        $this->authorize('sections.manage');
        $section->update($request->validated());
        return redirect()->route('classes.sections.index', $section->school_class_id)
                         ->with('success', 'Section updated.');
    }

    public function destroy(Section $section)
    {
        $this->authorize('sections.manage');
        $classId = $section->school_class_id;
        $section->delete();
        return redirect()->route('classes.sections.index', $classId)
                         ->with('success', 'Section deleted.');
    }
}
