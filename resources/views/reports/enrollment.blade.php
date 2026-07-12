<x-app-layout>
    <x-slot name="title">Enrollment Roster</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Enrollment Roster</h1>
            <p class="kia-page-sub">{{ $year->name }} · {{ $students->count() }} students</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('reports.enrollment', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-secondary">⬇ PDF</a>
            <a href="{{ route('reports.enrollment', array_merge(request()->query(), ['format' => 'excel'])) }}" class="btn btn-secondary">⬇ CSV</a>
            <a href="{{ route('reports.index') }}" class="btn btn-ghost">← Back</a>
        </div>
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr>
                    <th>#</th><th>Code</th><th>Name (EN)</th><th>Name (KM)</th><th>Gender</th>
                    @if($students->first() && isset($students->first()->branch_name))<th>Branch</th>@endif
                    <th>Class</th><th>Section</th><th>Roll</th>
                </tr></thead>
                <tbody>
                @foreach($students as $i => $s)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $s->student_code }}</td>
                        <td>{{ $s->name_en }}</td>
                        <td>{{ $s->name_km }}</td>
                        <td>{{ ucfirst($s->gender ?? '') }}</td>
                        @if(isset($s->branch_name))<td>{{ $s->branch_name }}</td>@endif
                        <td>{{ $s->class_name }}</td>
                        <td>{{ $s->section_name }}</td>
                        <td>{{ $s->roll_no ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
