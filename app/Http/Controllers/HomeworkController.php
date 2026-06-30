<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHomeworkRequest;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Section;
use App\Models\Subject;
use App\Services\HomeworkService;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    public function __construct(private HomeworkService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Homework::with(['section', 'subject', 'teacher'])
            ->whereNotNull('published_at')
            ->latest('published_at');

        if ($user->hasRole('teacher') && $user->staff) {
            $query->where('teacher_id', $user->staff->id);
        } elseif ($user->hasRole('student') && $user->student) {
            $sectionIds = $user->student->sections()->pluck('sections.id');
            $query->whereIn('section_id', $sectionIds);
        }

        return view('homework.index', ['homework' => $query->paginate(20)]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Homework::class);

        $sections = Section::with('schoolClass')->get();
        $subjects = Subject::orderBy('name')->get();

        return view('homework.create', compact('sections', 'subjects'));
    }

    public function store(StoreHomeworkRequest $request)
    {
        $this->authorize('create', Homework::class);

        $data = $request->validated();
        $data['teacher_id']   = $request->user()->staff->id;
        $data['published_at'] = $request->boolean('publish_now') ? now() : null;

        $attachment = $request->file('attachment');
        if ($attachment) {
            $data['attachment_path']          = $this->service->storeAttachment($attachment, 'homework');
            $data['attachment_original_name'] = $attachment->getClientOriginalName();
        }

        unset($data['attachment']);

        $hw = Homework::create($data);

        return redirect()->route('homework.show', $hw)
            ->with('success', __('engagement.homework_saved'));
    }

    public function show(Request $request, Homework $homework)
    {
        $user       = $request->user();
        $submission = null;

        if ($user->hasRole('student') && $user->student) {
            $submission = $homework->submissions()
                ->where('student_id', $user->student->id)
                ->first();
        }

        $submissions = null;
        if ($user->hasAnyRole(['admin', 'principal', 'teacher'])) {
            $submissions = $homework->submissions()->with('student')->get();
        }

        return view('homework.show', compact('homework', 'submission', 'submissions'));
    }

    // POST /homework/{homework}/submit
    public function submit(StoreSubmissionRequest $request, Homework $homework)
    {
        $this->authorize('submit', $homework);

        $submission = $this->service->submit(
            $homework,
            $request->user()->student,
            $request->file('file'),
            $request->note
        );

        return redirect()->route('homework.show', $homework)
            ->with('success', __('engagement.submission_saved'));
    }

    // POST /homework-submissions/{submission}/grade
    public function grade(Request $request, HomeworkSubmission $submission)
    {
        $this->authorize('grade', $submission);

        $request->validate([
            'grade'    => ['required', 'integer', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->grade($submission, $request->user()->staff, $request->grade, $request->feedback);

        return back()->with('success', __('engagement.graded'));
    }

    // GET /homework/{homework}/attachment  — gated download
    public function download(Request $request, Homework $homework)
    {
        if (! $homework->attachment_path) abort(404);
        // Anyone who can view the homework can download its attachment
        return response()->download(
            $this->service->downloadPath($homework->attachment_path),
            $homework->attachment_original_name ?? 'attachment'
        );
    }
}
