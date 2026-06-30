<x-app-layout>
    <x-slot name="title">Grade Scales</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Grade Scales</h1>
            <p class="kia-page-sub">{{ $scales->count() }} grades configured</p>
        </div>
        <a href="{{ route('grade-scales.create') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Grade
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
                        <th>Grade</th>
                        <th>Min Score</th>
                        <th>Max Score</th>
                        <th>GPA</th>
                        <th>Remark (EN)</th>
                        <th>Remark (KM)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scales as $scale)
                        <tr>
                            <td><span class="badge badge-primary">{{ $scale->grade }}</span></td>
                            <td>{{ $scale->min_score }}</td>
                            <td>{{ $scale->max_score }}</td>
                            <td>{{ $scale->gpa }}</td>
                            <td>{{ $scale->remark_en }}</td>
                            <td>{{ $scale->remark_km }}</td>
                            <td class="kia-table-actions">
                                <a href="{{ route('grade-scales.edit', $scale) }}" class="btn btn-sm btn-ghost">Edit</a>
                                <form method="POST" action="{{ route('grade-scales.destroy', $scale) }}" onsubmit="return confirm('Delete this grade?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="kia-table-empty">No grade scales configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
