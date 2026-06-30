@extends('layouts.app')
@section('title', 'Messages')
@section('content')
<div class="page-header">
    <h1>Messages</h1>
    <a href="{{ route('conversations.create') }}" class="btn btn-primary">New Message</a>
</div>
<div class="card">
    @forelse($conversations as $conv)
        <div class="list-item">
            <a href="{{ route('conversations.show', $conv) }}">
                <strong>{{ $conv->subject }}</strong>
                <small class="text-muted">{{ $conv->participants->pluck('name')->join(', ') }}</small>
            </a>
        </div>
    @empty
        <p class="empty-state">No conversations yet.</p>
    @endforelse
    {{ $conversations->links() }}
</div>
@endsection
