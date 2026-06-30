<x-app-layout>
    <x-slot name="title">Announcements</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Announcements</h1>
        </div>
        @can('create', \App\Models\Announcement::class)
            <a href="{{ route('announcements.create') }}" class="btn btn-primary">+ New</a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    <div class="kia-card">
        @forelse($announcements as $a)
            <div style="padding:1rem;border-bottom:1px solid var(--border)">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <a href="{{ route('announcements.show', $a) }}" style="font-weight:600">{{ $a->title }}</a>
                        <span class="kia-badge">{{ $a->audience }}</span>
                        @if(!$a->isPublished()) <span class="kia-badge" style="background:var(--warning-light);color:var(--warning)">Draft</span> @endif
                    </div>
                    <small style="color:var(--text-muted)">{{ $a->published_at?->diffForHumans() ?? 'Not published' }}</small>
                </div>
            </div>
        @empty
            <div style="padding:2rem;text-align:center;color:var(--text-muted)">No announcements yet.</div>
        @endforelse
        <div style="padding:1rem">{{ $announcements->links() }}</div>
    </div>
</x-app-layout>
