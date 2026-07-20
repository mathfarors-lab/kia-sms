<?php

namespace App\Http\Controllers;

use App\Http\Requests\Curriculum\StoreCurriculumTopicRequest;
use App\Models\ClassSubject;
use App\Models\CurriculumTopic;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\Auth;

class CurriculumController extends Controller
{
    public function index()
    {
        $this->authorize('curriculum.view');

        $classes = SchoolClass::withCount('classSubjects')->orderBy('name')->paginate(20);

        return view('curriculum.index', compact('classes'));
    }

    public function forClass(SchoolClass $class)
    {
        $this->authorize('curriculum.view');

        $class->load(['classSubjects' => function ($query) {
            $query->with(['subject', 'teacher.user'])
                ->withCount(['curriculumTopics', 'curriculumTopics as completed_topics_count' => function ($q) {
                    $q->where('is_completed', true);
                }]);
        }]);

        return view('curriculum.for-class', ['class' => $class]);
    }

    public function show(ClassSubject $classSubject)
    {
        $this->authorize('curriculum.view');

        $classSubject->load(['subject', 'schoolClass', 'teacher.user', 'curriculumTopics']);

        return view('curriculum.show', compact('classSubject'));
    }

    public function store(StoreCurriculumTopicRequest $request, ClassSubject $classSubject)
    {
        $classSubject->curriculumTopics()->create($request->validated());

        return back()->with('success', __('curriculum.topic_added'));
    }

    public function edit(CurriculumTopic $topic)
    {
        $this->authorize('curriculum.manage');

        $topic->load('classSubject.subject', 'classSubject.schoolClass');

        return view('curriculum.edit-topic', compact('topic'));
    }

    public function update(StoreCurriculumTopicRequest $request, CurriculumTopic $topic)
    {
        $topic->update($request->validated());

        return redirect()->route('curriculum.show', $topic->class_subject_id)
            ->with('success', __('curriculum.topic_updated'));
    }

    public function toggle(CurriculumTopic $topic)
    {
        $this->authorizeManageOrOwnSubject($topic);

        $topic->update([
            'is_completed' => ! $topic->is_completed,
            'completed_at' => $topic->is_completed ? null : now(),
        ]);

        return back()->with('success', __('curriculum.topic_updated'));
    }

    public function destroy(CurriculumTopic $topic)
    {
        $this->authorize('curriculum.manage');

        $topic->delete();

        return back()->with('success', __('curriculum.topic_deleted'));
    }

    /**
     * Full CRUD stays curriculum.manage-only (academic leadership defines the
     * syllabus); toggling completion is also open to the class_subject's
     * assigned teacher, since they're the one actually teaching it day to
     * day — same self-scoped-carve-out shape as staff evaluations.
     */
    private function authorizeManageOrOwnSubject(CurriculumTopic $topic): void
    {
        $user = Auth::user();
        if ($user->can('curriculum.manage')) {
            return;
        }

        abort_unless(
            $user->staff && $topic->classSubject->teacher_id === $user->staff->id,
            403
        );
    }
}
