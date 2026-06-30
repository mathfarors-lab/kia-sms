@extends('layouts.app')
@section('title', 'Library')
@section('content')
<div class="page-header">
    <h1>Library</h1>
    @can('create', \App\Models\Book::class)
        <a href="{{ route('books.create') }}" class="btn btn-primary">+ Add Book</a>
    @endcan
</div>
<div class="card">
    @forelse($books as $book)
        <div class="list-item">
            <div>
                <a href="{{ route('books.show', $book) }}"><strong>{{ $book->title }}</strong></a>
                <small>{{ $book->author }} · {{ $book->category }}</small>
            </div>
            <span class="badge {{ $book->available_copies > 0 ? 'badge-success' : 'badge-danger' }}">
                {{ $book->available_copies }}/{{ $book->total_copies }} available
            </span>
        </div>
    @empty
        <p class="empty-state">No books in the library yet.</p>
    @endforelse
    {{ $books->links() }}
</div>
@endsection
