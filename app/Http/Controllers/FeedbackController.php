<?php

namespace App\Http\Controllers;

use App\Http\Requests\Feedback\StoreFeedbackItemRequest;
use App\Models\FeedbackItem;
use App\Notifications\FeedbackReplied;
use App\Notifications\FeedbackStatusChanged;
use App\Models\User;
use App\Support\Permissions as P;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->can(P::FEEDBACK_VIEW)) {
            $items = FeedbackItem::with(['submitter', 'student', 'assignee'])
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->when($request->category, fn ($q) => $q->where('category', $request->category))
                ->latest()
                ->paginate(20)
                ->withQueryString();

            return view('feedback.index', ['items' => $items, 'isInbox' => true]);
        }

        if ($user->hasRole(['parent', 'student'])) {
            $items = FeedbackItem::where('submitted_by', $user->id)
                ->with('student')
                ->latest()
                ->paginate(20);

            return view('feedback.index', ['items' => $items, 'isInbox' => false]);
        }

        abort(403);
    }

    public function create()
    {
        $user = Auth::user();
        abort_unless($user->hasRole(['parent', 'student']), 403);

        $children = $user->hasRole('parent') ? $user->wards : collect();

        return view('feedback.create', compact('children'));
    }

    public function store(StoreFeedbackItemRequest $request)
    {
        $user = Auth::user();
        abort_unless($user->hasRole(['parent', 'student']), 403);

        $data = $request->validated();

        $studentId = null;
        if ($user->hasRole('student')) {
            $studentId = $user->student?->id;
        } elseif (!empty($data['student_id'])) {
            // A parent may only file feedback "about" one of their own wards.
            abort_unless($user->wards()->where('students.id', $data['student_id'])->exists(), 403);
            $studentId = $data['student_id'];
        }

        $attachmentPath = null;
        $attachmentName = null;
        if ($file = $request->file('attachment')) {
            $attachmentPath = $file->store('feedback/attachments', 'local');
            $attachmentName = $file->getClientOriginalName();
        }

        $feedback = FeedbackItem::create([
            'submitted_by'             => $user->id,
            'student_id'               => $studentId,
            'category'                 => $data['category'],
            'subject'                  => $data['subject'],
            'body'                     => $data['body'],
            'status'                   => 'open',
            'attachment_path'          => $attachmentPath,
            'attachment_original_name' => $attachmentName,
        ]);

        return redirect()->route('feedback.show', $feedback)
            ->with('success', __('feedback.submitted'));
    }

    public function show(FeedbackItem $feedback)
    {
        $this->authorizeAccess($feedback);

        $feedback->load(['submitter', 'student', 'assignee', 'replies.user']);

        $staffUsers = Auth::user()->can(P::FEEDBACK_MANAGE)
            ? User::permission(P::FEEDBACK_MANAGE)->orderBy('name')->get()
            : collect();

        return view('feedback.show', compact('feedback', 'staffUsers'));
    }

    public function reply(Request $request, FeedbackItem $feedback)
    {
        $this->authorizeAccess($feedback);

        $data = $request->validate(['body' => 'required|string|max:5000']);

        $reply = $feedback->replies()->create([
            'user_id' => Auth::id(),
            'body'    => $data['body'],
        ]);

        if ($reply->user_id !== $feedback->submitted_by) {
            $feedback->submitter->notify(new FeedbackReplied($feedback, $reply));
        }

        return redirect()->route('feedback.show', $feedback)->with('success', __('feedback.reply_added'));
    }

    public function assign(Request $request, FeedbackItem $feedback)
    {
        $this->authorize(P::FEEDBACK_MANAGE);

        $data = $request->validate(['assigned_to' => 'nullable|exists:users,id']);

        $feedback->update(['assigned_to' => $data['assigned_to'] ?? null]);

        return redirect()->route('feedback.show', $feedback)->with('success', __('feedback.assigned'));
    }

    public function updateStatus(Request $request, FeedbackItem $feedback)
    {
        $this->authorize(P::FEEDBACK_MANAGE);

        $data = $request->validate(['status' => 'required|in:' . implode(',', FeedbackItem::STATUSES)]);
        $newStatus = $data['status'];

        if (!$feedback->canTransitionTo($newStatus)) {
            return back()->withErrors(['status' => __('feedback.invalid_transition')]);
        }

        $feedback->update([
            'status'      => $newStatus,
            'resolved_at' => $newStatus === 'resolved' ? now() : $feedback->resolved_at,
        ]);

        $feedback->submitter->notify(new FeedbackStatusChanged($feedback, $newStatus));

        return redirect()->route('feedback.show', $feedback)->with('success', __('feedback.status_updated'));
    }

    public function reopen(FeedbackItem $feedback)
    {
        $this->authorize(P::FEEDBACK_MANAGE);

        abort_unless($feedback->canReopen(), 422);

        $feedback->update(['status' => 'open', 'resolved_at' => null]);

        $feedback->submitter->notify(new FeedbackStatusChanged($feedback, 'open'));

        return redirect()->route('feedback.show', $feedback)->with('success', __('feedback.reopened'));
    }

    public function downloadAttachment(FeedbackItem $feedback)
    {
        $this->authorizeAccess($feedback);

        abort_unless(
            $feedback->attachment_path && Storage::disk('local')->exists($feedback->attachment_path),
            404
        );

        return response()->download(
            Storage::disk('local')->path($feedback->attachment_path),
            $feedback->attachment_original_name ?? 'attachment'
        );
    }

    private function authorizeAccess(FeedbackItem $feedback): void
    {
        $user = Auth::user();

        if ($user->can(P::FEEDBACK_VIEW)) {
            return;
        }

        if ($feedback->submitted_by === $user->id) {
            return;
        }

        abort(403);
    }
}
