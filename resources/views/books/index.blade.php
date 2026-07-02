<x-app-layout>
    <x-slot name="title">Library</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Library</h1></div>
        @can('create', \App\Models\Book::class)
            <a href="{{ route('books.create') }}" class="btn btn-primary">+ Add Book</a>
        @endcan
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Available</th><th></th></tr></thead>
                <tbody>
                @forelse($books as $book)
                    <tr>
                        <td><a href="{{ route('books.show', $book) }}">{{ $book->title }}</a></td>
                        <td>{{ $book->author ?? '—' }}</td>
                        <td>{{ $book->category ?? '—' }}</td>
                        <td>
                            <span class="kia-badge {{ $book->available_copies > 0 ? '' : 'kia-badge-red' }}">
                                {{ $book->available_copies }}/{{ $book->total_copies }}
                            </span>
                        </td>
                        <td><a href="{{ route('books.show', $book) }}" class="btn btn-ghost" style="font-size:.75rem">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted)">No books yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $books->links() }}</div>
    </div>
</x-app-layout>
