<x-app-layout>
    <x-slot name="title">Mark Attendance</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Mark Attendance</h1>
            <p class="kia-page-sub">
                {{ $section->schoolClass->name }} — {{ $section->name }}
                &nbsp;|&nbsp;
                <strong>{{ $today->format('l, d M Y') }}</strong>
            </p>
        </div>
        <a href="{{ route('attendance.index') }}" class="btn btn-ghost">Back</a>
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    <div class="kia-card">
        <form method="POST" action="{{ route('attendance.store', $section) }}">
            @csrf
            <input type="hidden" name="section_id" value="{{ $section->id }}">
            <input type="hidden" name="date" value="{{ $today->toDateString() }}">

            <div class="kia-table-wrap">
                <table class="kia-table">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Student</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Excused</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $i => $student)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <input type="hidden" name="rows[{{ $i }}][student_id]" value="{{ $student->id }}">
                                {{ $student->name_en }}
                                @if($student->name_km)
                                    <small style="color:var(--kia-text-muted,#64748b)"> / {{ $student->name_km }}</small>
                                @endif
                            </td>
                            @php $status = $existing[$student->id] ?? 'present'; @endphp
                            @foreach(['present','absent','late','excused'] as $opt)
                            <td style="text-align:center">
                                <input type="radio" name="rows[{{ $i }}][status]" value="{{ $opt }}" {{ $status === $opt ? 'checked' : '' }}>
                            </td>
                            @endforeach
                            <td>
                                <input type="text" name="rows[{{ $i }}][remark]" class="form-control form-control-sm" placeholder="Optional">
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">No enrolled students found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($students->isNotEmpty())
            <div style="padding:1rem;display:flex;gap:.75rem;align-items:center">
                <button type="submit" class="btn btn-primary">Save Attendance</button>
                <button type="button" class="btn btn-ghost" onclick="markAll('present')">All Present</button>
                <button type="button" class="btn btn-ghost" onclick="markAll('absent')">All Absent</button>
            </div>
            @endif
        </form>
    </div>

    <script>
    function markAll(status) {
        document.querySelectorAll('input[type=radio][value=' + status + ']').forEach(r => r.checked = true);
    }
    </script>
</x-app-layout>
