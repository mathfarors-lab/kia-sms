<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Student;
use App\Notifications\ResultPublished;
use App\Services\GradingService;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function __construct(private GradingService $grading) {}

    public function index()
    {
        $this->authorize('exams.view');
        $exams = Exam::with('academicYear')->latest()->paginate(20);
        return view('exams.index', compact('exams'));
    }

    public function create()
    {
        $this->authorize('exams.manage');
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        return view('exams.create', compact('academicYears'));
    }

    public function store(Request $request)
    {
        $this->authorize('exams.manage');
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name'             => 'required|string|max:200',
            'type'             => 'required|in:monthly,midterm,final',
        ]);

        Exam::create($data + ['is_published' => false]);
        return redirect()->route('exams.index')->with('success', __('messages.created'));
    }

    public function edit(Exam $exam)
    {
        $this->authorize('exams.manage');
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        return view('exams.edit', compact('exam', 'academicYears'));
    }

    public function update(Request $request, Exam $exam)
    {
        $this->authorize('exams.manage');

        if ($exam->is_published) {
            return back()->withErrors(['exam' => __('exam.locked_published')]);
        }

        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name'             => 'required|string|max:200',
            'type'             => 'required|in:monthly,midterm,final',
        ]);

        $exam->update($data);
        return redirect()->route('exams.index')->with('success', __('messages.updated'));
    }

    public function destroy(Exam $exam)
    {
        $this->authorize('exams.manage');

        if ($exam->is_published) {
            return back()->withErrors(['exam' => __('exam.locked_published')]);
        }

        $exam->delete();
        return redirect()->route('exams.index')->with('success', __('messages.deleted'));
    }

    public function publish(Exam $exam)
    {
        $this->authorize('exams.publish');

        if ($exam->is_published) {
            return back()->withErrors(['exam' => __('exam.already_published')]);
        }

        $this->grading->computeResults($exam);
        $exam->update(['is_published' => true]);

        // Notify students asynchronously
        $studentIds = $exam->marks()->distinct()->pluck('student_id');
        Student::with('user')->whereIn('id', $studentIds)->get()
            ->each(fn ($s) => $s->user?->notify(new ResultPublished($exam)));

        return back()->with('success', __('exam.published_success'));
    }
}
