<x-app-layout>
    <x-slot name="title">Subjects</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Subjects</h1>
            <p class="kia-page-sub">{{ $subjects->total() }} total</p>
        </div>
        <a href="{{ route('subjects.create') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Subject
        </a>
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>Name (EN)</th>
                        <th>Name (KM)</th>
                        <th>Code</th>
                        <th>Full Mark</th>
                        <th>Classes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $subject)
                    <tr>
                        <td>{{ $subject->name_en }}</td>
                        <td>{{ $subject->name_km ?? '—' }}</td>
                        <td><code>{{ $subject->code }}</code></td>
                        <td>{{ $subject->full_mark }}</td>
                        <td>{{ $subject->school_classes_count }}</td>
                        <td class="text-right">
                            <a href="{{ route('subjects.edit', $subject) }}" class="btn btn-sm btn-ghost">Edit</a>
                            <form method="POST" action="{{ route('subjects.destroy', $subject) }}" style="display:inline" onsubmit="return confirm('Delete subject?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center">No subjects yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $subjects->links() }}</div>
    </div>
</x-app-layout>
