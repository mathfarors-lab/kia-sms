<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Timetable;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    private const DAYS    = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    private const PERIODS = [1, 2, 3, 4, 5, 6, 7, 8];

    /**
     * Standalone entry point — every section across every class, one click
     * from the sidebar. The Classes & Sections drill-down (pick a class,
     * then a section) still reaches the same timetable.show page; this is
     * an additional, more direct path for people who manage timetables
     * without needing to think in terms of "classes contain sections."
     */
    public function picker()
    {
        $this->authorize('timetables.manage');

        $sections = Section::with(['schoolClass', 'classTeacher.user'])
            ->get()
            ->sortBy([
                fn ($s) => $s->schoolClass?->name ?? '',
                fn ($s) => $s->name,
            ]);

        return view('timetables.index', compact('sections'));
    }

    public function index(Section $section)
    {
        $user      = auth()->user();
        $canManage = $user->can('timetables.manage');
        $isOwnSection = $user->staff && $section->class_teacher_id === $user->staff->id;

        if (!$canManage && !($user->can('timetables.view') && $isOwnSection)) {
            abort(403);
        }

        $section->load('schoolClass');

        $timetables = $section->timetables()
            ->with(['subject', 'teacher.user'])
            ->get()
            ->groupBy(fn($t) => $t->day . '_' . $t->period);

        $subjects = Subject::all();
        $staff    = Staff::with('user')->get();

        return view('timetable.show', compact('section', 'timetables', 'subjects', 'staff', 'canManage'));
    }

    public function store(Section $section, Request $request)
    {
        $this->authorize('timetables.manage');

        $data = $request->validate([
            'subject_id'  => ['required', 'exists:subjects,id'],
            'teacher_id'  => ['nullable', 'exists:staff,id'],
            'day'         => ['required', 'in:monday,tuesday,wednesday,thursday,friday'],
            'period'      => ['required', 'integer', 'min:1', 'max:8'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'room'        => ['nullable', 'string', 'max:50'],
        ]);

        if (Timetable::hasConflict($section->id, $data['day'], $data['period'])) {
            return response()->json(['error' => 'This period is already taken for this section.'], 422);
        }

        if (!empty($data['teacher_id']) && Timetable::hasTeacherConflict($data['teacher_id'], $data['day'], $data['period'])) {
            return response()->json(['error' => 'Teacher has another class at this time.'], 422);
        }

        $slot = $section->timetables()->create($data);
        $slot->load(['subject', 'teacher.user']);

        return response()->json(['success' => true, 'slot' => $slot]);
    }

    public function destroy(Timetable $timetable)
    {
        $this->authorize('timetables.manage');
        $timetable->delete();
        return response()->json(['success' => true]);
    }
}
