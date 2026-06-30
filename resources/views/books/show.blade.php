@extends('layouts.app')
@section('title', $book->title)
@section('content')
<div class="page-header">
    <h1>{{ $book->title }}</h1>
    <div class="actions">
        @can('issue', $book)
            @if($book->available_copies > 0)
                <a href="{{ route('books.issue', $book) }}" class="btn btn-primary">Issue</a>
            @else
                <span class="badge badge-danger">No copies available</span>
            @endif
        @endcan
        @can('update', $book)
            <a href="{{ route('books.edit', $book) }}" class="btn btn-secondary">Edit</a>
        @endcan
    </div>
</div>

<div class="card">
    <p><strong>Author:</strong> {{ $book->author ?? '—' }}</p>
    <p><strong>ISBN:</strong> {{ $book->isbn ?? '—' }}</p>
    <p><strong>Category:</strong> {{ $book->category ?? '—' }}</p>
    <p><strong>Available:</strong> {{ $book->available_copies }}/{{ $book->total_copies }}</p>
</div>

<div class="card mt-4">
    <h3>Issue History</h3>
    @forelse($issues as $issue)
        <div class="list-item">
            <div>
                <strong>{{ $issue->student->name_en }}</strong>
                <small>Issued: {{ $issue->issued_at->format('d M Y') }} · Due: {{ $issue->due_date->format('d M Y') }}</small>
            </div>
            @if($issue->returned_at)
                <span class="badge badge-success">Returned {{ $issue->returned_at->format('d M') }}
                    @if($issue->fine_amount > 0) · Fine: ${{ $issue->fine_amount }} @endif
                </span>
            @else
                <div>
                    <span class="badge {{ $issue->isOverdue() ? 'badge-danger' : 'badge-warning' }}">
                        {{ $issue->isOverdue() ? 'Overdue' : 'Out' }}
                    </span>
                    @can('return_', $book)
                        <form method="POST" action="{{ route('book-issues.return', $issue) }}" style="display:inline"
                              onsubmit="return confirm('Mark as returned?')">
                            @csrf
                            <button class="btn btn-sm btn-secondary" type="submit">Return</button>
                        </form>
                    @endcan
                </div>
            @endif
        </div>
    @empty
        <p class="empty-state">No issues yet.</p>
    @endforelse
    {{ $issues->links() }}
</div>
@endsection
