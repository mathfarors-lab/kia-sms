<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamResult;
use Illuminate\Support\Facades\Auth;

class StudentPortalController extends Controller
{
    public function attendance()
    {
        $user = Auth::user();
        abort_unless($user->hasRole('student'), 403);

        $student = $user->student;
        abort_unless($student !== null, 404);

        $records = Attendance::where('student_id', $student->id)
            ->orderByDesc('date')
            ->paginate(30);

        $total   = Attendance::where('student_id', $student->id)->count();
        $present = Attendance::where('student_id', $student->id)
            ->whereIn('status', ['present', 'late'])->count();
        $attendancePct = $total > 0 ? round($present / $total * 100) : null;

        // Monthly breakdown (PHP-side, DB-agnostic)
        $monthly = Attendance::where('student_id', $student->id)
            ->orderByDesc('date')
            ->get()
            ->groupBy(fn ($a) => $a->date->format('Y-m'))
            ->map(fn ($group, $month) => [
                'month'         => $month,
                'total'         => $group->count(),
                'present_count' => $group->whereIn('status', ['present', 'late'])->count(),
            ]);

        // Published exam results
        $results = ExamResult::where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q->where('is_published', true))
            ->with('exam')
            ->latest()
            ->get();

        $publishedExams = Exam::published()->latest()->get();

        return view('student.attendance', compact(
            'student', 'records', 'attendancePct', 'monthly', 'results', 'publishedExams'
        ));
    }
}
