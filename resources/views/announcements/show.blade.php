@extends('layouts.app')
@section('title', $announcement->title)
@section('content')
<div class="page-header">
    <h1>{{ $announcement->title }}</h1>
    <div class="actions">
        @can('publish', $announcement)
            @if(!$announcement->isPublished())
                <form method="POST" action="{{ route('announcements.publish', $announcement) }}" style="display:inline">
                    @csrf
                    <button class="btn btn-primary">Publish</button>
                </form>
            @endif
        @endcan
        @can('update', $announcement)
            <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-secondary">Edit</a>
        @endcan
        @can('delete', $announcement)
            <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" style="display:inline"
                  onsubmit="return confirm('Delete?')">
                @csrf @method('DELETE')
                <button class="btn btn-danger">Delete</button>
            </form>
        @endcan
    </div>
</div>

<div class="card">
    <div class="meta text-muted mb-2">
        <span>By {{ $announcement->author->name }}</span> ·
        <span>{{ $announcement->published_at?->format('d M Y H:i') ?? 'Draft' }}</span> ·
        <span class="badge">{{ $announcement->audience }}</span>
    </div>
    <div class="prose">
        {!! nl2br(e($announcement->body_en)) !!}
    </div>
    @if($announcement->body_km)
        <hr>
        <div class="prose" style="font-family: 'Khmer OS', sans-serif">
            {!! nl2br(e($announcement->body_km)) !!}
        </div>
    @endif
</div>
@endsection
