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

        $classes  = SchoolClass::orderBy('name')->get();
        $sections = Section::with('schoolClass')->orderBy('name')->get();

        return view('announcements.create', compact('classes', 'sections'));
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $this->authorize('create', Announcement::class);

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

        $classes  = SchoolClass::orderBy('name')->get();
        $sections = Section::with('schoolClass')->orderBy('name')->get();

        return view('announcements.edit', compact('announcement', 'classes', 'sections'));
    }

    public function update(StoreAnnouncementRequest $request, Announcement $announcement)
    {
        $this->authorize('update', $announcement);
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
}
