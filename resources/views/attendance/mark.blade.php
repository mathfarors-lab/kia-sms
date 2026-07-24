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

    <form method="POST" action="{{ route('attendance.store', $section) }}">
        @csrf
        <input type="hidden" name="section_id" value="{{ $section->id }}">
        <input type="hidden" name="date" value="{{ $today->toDateString() }}">

        @if($students->isNotEmpty())
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-bottom:16px;">
            <button type="button" class="btn btn-outline" onclick="markAll('present')">Mark All Present</button>
            <button type="button" class="btn btn-outline" onclick="markAll('absent')">Mark All Absent</button>
            <button type="submit" class="btn btn-primary">Save Attendance</button>
        </div>
        @endif

        <div class="kia-card">
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
        </div>
    </form>

    <script>
    function markAll(status) {
        document.querySelectorAll('input[type=radio][value=' + status + ']').forEach(r => r.checked = true);
    }
    </script>
</x-app-layout>
