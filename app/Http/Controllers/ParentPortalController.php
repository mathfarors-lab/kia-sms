<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class ParentPortalController extends Controller
{
    public function children()
    {
        $user = Auth::user();
        abort_unless($user->hasRole('parent'), 403);

        $children = $user->wards()->with([
            'sections.schoolClass',
            'invoices' => fn ($q) => $q->unpaid()->latest()->limit(3),
        ])->get();

        foreach ($children as $child) {
            $total   = Attendance::where('student_id', $child->id)->count();
            $present = Attendance::where('student_id', $child->id)
                ->whereIn('status', ['present', 'late'])->count();
            $child->attendance_pct = $total > 0 ? round($present / $total * 100) : null;

            $child->latest_exams = Exam::published()
                ->whereHas('marks', fn ($q) => $q->where('student_id', $child->id))
                ->latest()->limit(3)->get();
        }

        $announcements = Announcement::query()
            ->visibleTo($user)
            ->whereNotNull('published_at')
            ->latest()
            ->take(5)
            ->get();

        return view('parent.children', compact('children', 'announcements'));
    }

    public function childDetail(Student $student)
    {
        $user = Auth::user();
        abort_unless($user->hasRole('parent'), 403);

        // IDOR guard — this child must belong to the authenticated parent
        abort_unless(
            $user->wards()->where('students.id', $student->id)->exists(),
            403
        );

        $total   = Attendance::where('student_id', $student->id)->count();
        $present = Attendance::where('student_id', $student->id)
            ->whereIn('status', ['present', 'late'])->count();
        $attendancePct = $total > 0 ? round($present / $total * 100) : null;

        $records = Attendance::where('student_id', $student->id)
            ->orderByDesc('date')
            ->paginate(30);

        $publishedExams = Exam::published()
            ->whereHas('marks', fn ($q) => $q->where('student_id', $student->id))
            ->with(['marks' => fn ($q) => $q->where('student_id', $student->id)->with('subject')])
            ->latest()
            ->get();

        $invoices = Invoice::where('student_id', $student->id)
            ->with('academicYear')
            ->latest()
            ->get();

        return view('parent.child-detail', compact(
            'student', 'attendancePct', 'records', 'publishedExams', 'invoices'
        ));
    }
}
