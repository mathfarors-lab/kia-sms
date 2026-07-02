<x-app-layout>
    <x-slot name="title">{{ $announcement->title }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $announcement->title }}</h1>
            <p class="kia-page-sub">
                By {{ $announcement->author->name }} ·
                {{ $announcement->published_at?->format('d M Y H:i') ?? 'Draft' }} ·
                <span class="kia-badge">{{ ucfirst($announcement->audience) }}</span>
            </p>
        </div>
        <div style="display:flex;gap:.5rem">
            @can('publish', $announcement)
                @if(!$announcement->isPublished())
                    <form method="POST" action="{{ route('announcements.publish', $announcement) }}">
                        @csrf <button class="btn btn-primary">Publish</button>
                    </form>
                @endif
            @endcan
            @can('update', $announcement)
                <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-ghost">Edit</a>
            @endcan
            @can('delete', $announcement)
                <form method="POST" action="{{ route('announcements.destroy', $announcement) }}"
                      onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger">Delete</button>
                </form>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    <div class="kia-card">
        <div class="kia-card-body">
            <div style="white-space:pre-wrap">{{ $announcement->body_en }}</div>
            @if($announcement->body_km)
                <hr style="margin:1.5rem 0">
                <div style="white-space:pre-wrap;font-family:'Khmer OS',sans-serif">{{ $announcement->body_km }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
