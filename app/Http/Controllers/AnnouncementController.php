<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Announcement::class);

        $announcements = Announcement::visibleTo($request->user())
            ->latest('published_at')
            ->paginate(20);

        return view('announcements.index', compact('announcements'));
    }

    public function create()
    {
        $this->authorize('create', Announcement::class);

        [$classes, $sections, $canBroadcastAll] = $this->audienceOptions();

        return view('announcements.create', compact('classes', 'sections', 'canBroadcastAll'));
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $this->authorize('create', Announcement::class);
        $this->authorizeAudience($request->user(), $request->input('audience'), $request->input('target_id'));

        $data = $request->validated();
        $data['posted_by'] = $request->user()->id;

        $announcement = Announcement::create($data);

        if ($request->boolean('publish_now')) {
            $this->service->publish($announcement);
        }

        return redirect()->route('announcements.show', $announcement)
            ->with('success', __('engagement.announcement_saved'));
    }

    public function show(Announcement $announcement)
    {
        $this->authorize('view', $announcement);
        return view('announcements.show', compact('announcement'));
    }

    public function edit(Announcement $announcement)
    {
        $this->authorize('update', $announcement);

        [$classes, $sections, $canBroadcastAll] = $this->audienceOptions();

        return view('announcements.edit', compact('announcement', 'classes', 'sections', 'canBroadcastAll'));
    }

    public function update(StoreAnnouncementRequest $request, Announcement $announcement)
    {
        $this->authorize('update', $announcement);
        $this->authorizeAudience($request->user(), $request->input('audience'), $request->input('target_id'));

        $announcement->update($request->validated());

        return redirect()->route('announcements.show', $announcement)
            ->with('success', __('engagement.announcement_saved'));
    }

    public function destroy(Announcement $announcement)
    {
        $this->authorize('delete', $announcement);
        $announcement->delete();

        return redirect()->route('announcements.index')
            ->with('success', __('engagement.announcement_deleted'));
    }

    public function publish(Announcement $announcement)
    {
        $this->authorize('publish', $announcement);
        $this->service->publish($announcement);

        return redirect()->route('announcements.show', $announcement)
            ->with('success', __('engagement.announcement_published'));
    }

    /**
     * The class/section pickers, scoped to a teacher's own sections so the
     * form itself can't offer what authorizeAudience() would reject anyway.
     * canBroadcastAll is likewise UX only — the server-side enforcement is
     * authorizeAudience(), not this.
     */
    private function audienceOptions(): array
    {
        $user = auth()->user();

        $classesQuery  = SchoolClass::orderBy('name');
        $sectionsQuery = Section::with('schoolClass')->orderBy('name');
        $canBroadcastAll = true;

        if ($user->hasRole('teacher')) {
            $mySectionIds = $user->staff?->accessibleSectionIds() ?? collect();
            $sectionsQuery->whereIn('id', $mySectionIds);
            $classesQuery->whereIn('id', Section::whereIn('id', $mySectionIds)->pluck('school_class_id'));
            $canBroadcastAll = false;
        }

        return [$classesQuery->get(), $sectionsQuery->get(), $canBroadcastAll];
    }

    /**
     * ANNOUNCEMENTS_CREATE only proves a teacher may post an announcement
     * SOMEWHERE — it does not scope WHO receives it. Without this, a
     * teacher could submit audience=all (school-wide) or any section/grade
     * they don't teach, and publish() — which they can call on their own
     * record — would actually fan out notifications to those recipients via
     * AnnouncementService::recipientQuery(). Principal/admin are the only
     * roles meant to broadcast beyond their own sections.
     */
    private function authorizeAudience($user, ?string $audience, $targetId): void
    {
        if (! $user->hasRole('teacher')) {
            return;
        }

        abort_if($audience === 'all', 403);

        $mySectionIds = $user->staff?->accessibleSectionIds() ?? collect();

        if ($audience === 'class') {
            abort_unless($mySectionIds->contains((int) $targetId), 403);
            return;
        }

        if ($audience === 'grade') {
            $myClassIds = Section::whereIn('id', $mySectionIds)->pluck('school_class_id');
            abort_unless($myClassIds->contains((int) $targetId), 403);
        }
    }
}
