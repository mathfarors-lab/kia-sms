<x-app-layout>
    <x-slot name="title">Homework</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Homework</h1></div>
        @can('create', \App\Models\Homework::class)
            <a href="{{ route('homework.create') }}" class="btn btn-primary">+ Assign</a>
        @endcan
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr>
                    <th>Title</th><th>Section</th><th>Subject</th><th>Due Date</th><th></th>
                </tr></thead>
                <tbody>
                @forelse($homework as $hw)
                    <tr>
                        <td><a href="{{ route('homework.show', $hw) }}">{{ $hw->title }}</a></td>
                        <td>{{ $hw->section->name }}</td>
                        <td>{{ $hw->subject->name_en }}</td>
                        <td>{{ $hw->due_date->format('d M Y') }}</td>
                        <td><a href="{{ route('homework.show', $hw) }}" class="btn btn-ghost" style="font-size:.75rem">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted)">No homework yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $homework->links() }}</div>
    </div>
</x-app-layout>
