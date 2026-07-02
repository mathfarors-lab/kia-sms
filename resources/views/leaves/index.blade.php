<x-app-layout>
    <x-slot name="title">Leave Requests</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Leave Requests</h1></div>
        @can(App\Support\Permissions::LEAVES_SUBMIT)
            <a href="{{ route('leaves.create') }}" class="btn btn-primary">+ Request Leave</a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr>
                    @if(auth()->user()->hasAnyRole(['admin','principal']))<th>Staff</th>@endif
                    <th>Type</th><th>From</th><th>To</th><th>Reason</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                @forelse($leaves as $leave)
                    <tr>
                        @if(auth()->user()->hasAnyRole(['admin','principal']))
                            <td>{{ $leave->user->name }}</td>
                        @endif
                        <td>{{ ucfirst($leave->type) }}</td>
                        <td>{{ $leave->start_date->format('d M Y') }}</td>
                        <td>{{ $leave->end_date->format('d M Y') }}</td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $leave->reason ?? '—' }}</td>
                        <td>
                            @if($leave->status === 'approved')
                                <span class="kia-badge" style="background:var(--success-light,#d1fae5);color:var(--success,#065f46)">Approved</span>
                            @elseif($leave->status === 'rejected')
                                <span class="kia-badge" style="background:var(--danger-light);color:var(--danger)">Rejected</span>
                            @else
                                <span class="kia-badge" style="background:var(--warning-light,#fef3c7);color:var(--warning,#92400e)">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($leave->isPending() && auth()->user()->hasAnyRole(['admin','principal']) && $leave->user_id !== auth()->id())
                                <div style="display:flex;gap:.4rem">
                                    <form method="POST" action="{{ route('leaves.approve', $leave) }}">
                                        @csrf
                                        <button class="btn btn-ghost" style="font-size:.75rem;color:var(--success,#065f46)" type="submit">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('leaves.reject', $leave) }}">
                                        @csrf
                                        <button class="btn btn-ghost" style="font-size:.75rem;color:var(--danger)" type="submit">Reject</button>
                                    </form>
                                </div>
                            @elseif($leave->reviewer_note)
                                <span style="font-size:.75rem;color:var(--text-muted)">{{ $leave->reviewer_note }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted)">No leave requests.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $leaves->links() }}</div>
    </div>
</x-app-layout>
