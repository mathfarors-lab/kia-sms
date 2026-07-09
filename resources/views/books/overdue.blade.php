<x-app-layout>
    <x-slot name="title">{{ __('Overdue Books') }}</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">{{ __('Overdue Books') }}</h1></div>
        <a href="{{ route('books.index') }}" class="btn btn-ghost">{{ __('Back to Catalog') }}</a>
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr>
                    <th>{{ __('Book') }}</th><th>{{ __('Student') }}</th><th>{{ __('Due Date') }}</th><th>{{ __('Days Late') }}</th>
                </tr></thead>
                <tbody>
                @forelse($issues as $issue)
                    <tr>
                        <td>{{ $issue->book->title }}</td>
                        <td>{{ $issue->student->name_en }}</td>
                        <td>{{ $issue->due_date->format('d M Y') }}</td>
                        <td>
                            <span class="kia-badge" style="background:var(--danger-light,#fee2e2);color:var(--danger,#991b1b)">
                                {{ now()->startOfDay()->diffInDays($issue->due_date) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-muted)">{{ __('No overdue books.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $issues->links() }}</div>
    </div>
</x-app-layout>
