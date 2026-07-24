<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\MarkAttendanceRequest;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Section;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    public function index(Request $request)
    {
        // attendance.view (not .mark) is what student/parent hold, for their
        // OWN attendance via student.attendance / parent child-detail —
        // neither route touches this method. This page is a staff tool for
        // picking a section to manage, so it must gate on the same
        // permission the sidebar link already assumes (attendance.mark);
        // gating on the broader .view let student/parent load it directly
        // by URL and see every section's teacher and daily counts.
        $this->authorize('attendance.mark');
        $user = auth()->user();

        $query = Section::with(['schoolClass', 'classTeacher.user'])
            ->withCount([
                'attendances as today_present' => function ($q) {
                    $q->whereDate('date', today())->where('status', 'present');
                },
                'attendances as today_absent' => function ($q) {
                    $q->whereDate('date', today())->where('status', 'absent');
                },
            ]);

        // Same convention as StudentController / authorizeSectionAccess():
        // teacher is the one attendance.mark holder scoped to their own
        // sections. Admin/principal see everything, as their role requires.
        if ($user->hasRole('teacher')) {
            $query->whereIn('id', $user->staff?->accessibleSectionIds() ?? collect());
        }

        $sections = $query->paginate(20);

        $activeYear = AcademicYear::where('is_active', true)->first();

        return view('attendance.index', compact('sections', 'activeYear'));
    }

    public function markForm(Section $section)
    {
        $this->authorize('attendance.mark');
        $this->authorizeSectionAccess($section);
        $section->load('schoolClass');

        $students = $this->sectionRoster($section);

        $existing = Attendance::forSection($section->id)
            ->forDate(today())
            ->pluck('status', 'student_id');

        $today = Carbon::today();
        return view('attendance.mark', compact('section', 'students', 'existing', 'today'));
    }

    public function mark(MarkAttendanceRequest $request, Section $section)
    {
        $this->authorize('attendance.mark');
        $this->authorizeSectionAccess($section);

        $result = $this->service->markSection($section, $request->input('rows', []), auth()->user());

        return redirect()->route('attendance.index')
                         ->with('success', "Attendance saved for {$result['saved']} students.");
    }

    /** This section's actual enrolled roster for the active year — not every enrolled student in the school. */
    private function sectionRoster(Section $section)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = $section->students()->where('status', 'enrolled');
        if ($activeYear) {
            $query->wherePivot('academic_year_id', $activeYear->id);
        }

        return $query->orderBy('name_en')->get();
    }

    /**
     * attendance.mark only proves a user may mark attendance SOMEWHERE — it
     * does not scope WHICH section. Without this, any teacher could mark (or
     * overwrite) another teacher's section by guessing the URL, since
     * markForm()/mark() both take a raw {section} route param. Admin and
     * principal are the only roles meant to reach any section; every other
     * attendance.mark holder (teacher) is scoped to their own.
     */
    private function authorizeSectionAccess(Section $section): void
    {
        $user = auth()->user();

        if (! $user->hasRole('teacher')) {
            return;
        }

        if (! $user->staff || ! $user->staff->accessibleSectionIds()->contains($section->id)) {
            abort(403);
        }
    }
}
