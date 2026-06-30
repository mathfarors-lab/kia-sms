<x-app-layout>
    <x-slot name="title">Fee Structures</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Fee Structures</h1>
            <p class="kia-page-sub">{{ $fees->total() }} total</p>
        </div>
        <a href="{{ route('fee-structures.create') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Fee
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
                        <th>Name</th>
                        <th>Class</th>
                        <th>Amount (USD)</th>
                        <th>Frequency</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fees as $fee)
                        <tr>
                            <td>{{ $fee->name }}</td>
                            <td>{{ $fee->schoolClass?->name ?? 'All Classes' }}</td>
                            <td>${{ number_format($fee->amount, 2) }}</td>
                            <td><span class="badge badge-secondary">{{ ucfirst($fee->frequency) }}</span></td>
                            <td>
                                @if($fee->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-warn">Inactive</span>
                                @endif
                            </td>
                            <td class="kia-table-actions">
                                <a href="{{ route('fee-structures.edit', $fee) }}" class="btn btn-sm btn-ghost">Edit</a>
                                <form method="POST" action="{{ route('fee-structures.destroy', $fee) }}" onsubmit="return confirm('Delete this fee?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="kia-table-empty">No fee structures yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="kia-pagination">{{ $fees->links() }}</div>
    </div>
</x-app-layout>
