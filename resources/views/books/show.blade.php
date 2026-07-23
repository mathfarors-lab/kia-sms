<x-app-layout>
    <x-slot name="title">{{ $book->title }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $book->title }}</h1>
            @if($book->author)
                <p class="kia-page-sub">by {{ $book->author }}</p>
            @endif
        </div>
        <div style="display:flex;gap:.5rem">
            @can('issue', $book)
                @if($book->available_copies > 0)
                    <a href="{{ route('books.issue', $book) }}" class="btn btn-primary">Issue Book</a>
                @else
                    <span class="kia-badge" style="background:var(--danger-light);color:var(--danger)">No copies available</span>
                @endif
            @endcan
            @can('update', $book)
                <a href="{{ route('books.edit', $book) }}" class="btn btn-secondary">Edit</a>
            @endcan
        </div>
    </div>

    <div class="kia-card" style="max-width:480px;margin-bottom:1rem">
        <div class="kia-card-body">
            <table style="width:100%;border-collapse:collapse">
                <tr><td style="padding:.4rem 1rem .4rem 0;color:var(--text-muted);width:40%">ISBN</td><td>{{ $book->isbn ?? '—' }}</td></tr>
                <tr><td style="padding:.4rem 1rem .4rem 0;color:var(--text-muted)">Category</td><td>{{ $book->category ?? '—' }}</td></tr>
                <tr><td style="padding:.4rem 1rem .4rem 0;color:var(--text-muted)">Total Copies</td><td>{{ $book->total_copies }}</td></tr>
                <tr><td style="padding:.4rem 1rem .4rem 0;color:var(--text-muted)">Available</td>
                    <td><span class="kia-badge {{ $book->available_copies > 0 ? '' : 'kia-badge-red' }}">{{ $book->available_copies }}</span></td></tr>
            </table>
        </div>
    </div>

    @can('viewIssueHistory', $book)
    <div class="kia-card">
        <div class="kia-card-header"><h3 class="kia-card-title">Issue History</h3></div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr><th>Student</th><th>Issued</th><th>Due</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($issues as $issue)
                    <tr>
                        <td>{{ $issue->student->name_en }}</td>
                        <td>{{ $issue->issued_at->format('d M Y') }}</td>
                        <td>{{ $issue->due_date->format('d M Y') }}</td>
                        <td>
                            @if($issue->returned_at)
                                <span class="kia-badge">Returned {{ $issue->returned_at->format('d M') }}</span>
                                @if($issue->fine_amount > 0)
                                    <span class="kia-badge" style="background:var(--danger-light);color:var(--danger)">Fine: ${{ $issue->fine_amount }}</span>
                                @endif
                            @elseif($issue->isOverdue())
                                <span class="kia-badge" style="background:var(--danger-light);color:var(--danger)">Overdue</span>
                            @else
                                <span class="kia-badge" style="background:var(--warning-light,#fef3c7);color:var(--warning,#92400e)">Out</span>
                            @endif
                        </td>
                        <td>
                            @if(!$issue->returned_at)
                                @can('return_', $book)
                                    <form method="POST" action="{{ route('book-issues.return', $issue) }}"
                                          onsubmit="return confirm('Mark as returned?')">
                                        @csrf
                                        <button class="btn btn-ghost" style="font-size:.75rem" type="submit">Return</button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted)">No issues yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $issues->links() }}</div>
    </div>
    @endcan
</x-app-layout>
