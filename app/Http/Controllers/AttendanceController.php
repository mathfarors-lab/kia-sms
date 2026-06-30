<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\MarkAttendanceRequest;
use App\Models\Attendance;
use App\Models\Section;
use App\Models\Student;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    public function index(Request $request)
    {
        $this->authorize('attendance.view');
        $sections = Section::with(['schoolClass', 'classTeacher.user'])
            ->withCount([
                'attendances as today_present' => function ($q) {
                    $q->whereDate('date', today())->where('status', 'present');
                },
                'attendances as today_absent' => function ($q) {
                    $q->whereDate('date', today())->where('status', 'absent');
                },
            ])
            ->paginate(20);

        return view('attendance.index', compact('sections'));
    }

    public function markForm(Section $section)
    {
        $this->authorize('attendance.mark');
        $section->load('schoolClass');

        // MVP: load all enrolled students (enrollments in Phase 3)
        $students = Student::where('status', 'enrolled')->get();

        $existing = Attendance::forSection($section->id)
            ->forDate(today())
            ->pluck('status', 'student_id');

        $today = Carbon::today();
        return view('attendance.mark', compact('section', 'students', 'existing', 'today'));
    }

    public function mark(MarkAttendanceRequest $request, Section $section)
    {
        $this->authorize('attendance.mark');

        $result = $this->service->markSection($section, $request->input('rows', []), auth()->user());

        return redirect()->route('attendance.index')
                         ->with('success', "Attendance saved for {$result['saved']} students.");
    }
}
