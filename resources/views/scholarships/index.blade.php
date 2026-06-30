<x-app-layout>
    <x-slot name="title">Scholarships</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Scholarships</h1>
            <p class="kia-page-sub">{{ $scholarships->total() }} records</p>
        </div>
        <a href="{{ route('scholarships.create') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Scholarship
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
                        <th>Student</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Reason</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scholarships as $s)
                        <tr>
                            <td>{{ $s->student->name_en }}</td>
                            <td><span class="badge badge-secondary">{{ ucfirst($s->type) }}</span></td>
                            <td>{{ $s->type === 'percent' ? $s->value . '%' : '$' . number_format($s->value, 2) }}</td>
                            <td>{{ $s->reason ?? '—' }}</td>
                            <td>
                                @if($s->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-warn">Inactive</span>
                                @endif
                            </td>
                            <td class="kia-table-actions">
                                <a href="{{ route('scholarships.edit', $s) }}" class="btn btn-sm btn-ghost">Edit</a>
                                <form method="POST" action="{{ route('scholarships.destroy', $s) }}" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="kia-table-empty">No scholarships yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="kia-pagination">{{ $scholarships->links() }}</div>
    </div>
</x-app-layout>
