<x-app-layout>
    <x-slot name="title">Academic Years</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Academic Years</h1>
            <p class="kia-page-sub">{{ $years->total() }} total</p>
        </div>
        <a href="{{ route('academic-years.create') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Year
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
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($years as $year)
                    <tr>
                        <td>{{ $year->name }}</td>
                        <td>{{ $year->start_date->format('d M Y') }}</td>
                        <td>{{ $year->end_date->format('d M Y') }}</td>
                        <td>
                            @if($year->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-ghost">Inactive</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @unless($year->is_active)
                            <form method="POST" action="{{ route('academic-years.update', $year) }}" style="display:inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="name" value="{{ $year->name }}">
                                <input type="hidden" name="start_date" value="{{ $year->start_date->toDateString() }}">
                                <input type="hidden" name="end_date" value="{{ $year->end_date->toDateString() }}">
                                <input type="hidden" name="is_active" value="1">
                                <button class="btn btn-sm btn-outline">Set Active</button>
                            </form>
                            @endunless
                            <a href="{{ route('academic-years.edit', $year) }}" class="btn btn-sm btn-ghost">Edit</a>
                            <form method="POST" action="{{ route('academic-years.destroy', $year) }}" style="display:inline" onsubmit="return confirm('Delete this year?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center">No academic years yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $years->links() }}</div>
    </div>
</x-app-layout>
