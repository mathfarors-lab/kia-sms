<x-app-layout>
    <x-slot name="title">Attendance Summary</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Attendance Summary</h1>
            <p class="kia-page-sub">{{ $year->name }}</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('reports.attendance', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-secondary">⬇ PDF</a>
            <a href="{{ route('reports.index') }}" class="btn btn-ghost">← Back</a>
        </div>
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr>
                    <th>Code</th><th>Student</th>
                    @if($rows->first() && isset($rows->first()->branch_name))<th>Branch</th>@endif
                    <th>Class</th><th>Total</th><th>Present</th><th>Absent</th><th>Late</th><th>Rate</th>
                </tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->student_code }}</td>
                        <td>{{ $row->name_en }}</td>
                        @if(isset($row->branch_name))<td>{{ $row->branch_name }}</td>@endif
                        <td>{{ $row->class_name }} / {{ $row->section_name }}</td>
                        <td>{{ $row->total_days }}</td>
                        <td>{{ $row->present }}</td>
                        <td>{{ $row->absent }}</td>
                        <td>{{ $row->late }}</td>
                        <td>
                            <span class="kia-badge {{ $row->rate >= 80 ? '' : 'kia-badge-red' }}">{{ $row->rate }}%</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted)">No data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
