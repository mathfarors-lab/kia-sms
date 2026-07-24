<x-app-layout>
    <x-slot name="title">{{ $class->name }} - {{ $section->name }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('classes.index') }}">Classes</a>
        <span class="sep">/</span>
        <a href="{{ route('classes.show', $class) }}">{{ $class->name }}</a>
        <span class="sep">/</span>
        <span>{{ $section->name }}</span>
    </div>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $class->name }} - {{ $section->name }}</h1>
            <p class="kia-page-sub">
                {{ __('Class Teacher') }}: {{ $section->classTeacher?->user?->name ?? '—' }}
                @if($activeYear) &nbsp;|&nbsp; {{ $activeYear->name }} @endif
            </p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('attendance.mark', $section) }}" class="btn btn-outline">{{ __('Mark Attendance') }}</a>
            <a href="{{ route('timetable.show', $section) }}" class="btn btn-outline">Timetable</a>
            <a href="{{ route('sections.edit', $section) }}" class="btn btn-ghost">{{ __('Edit Section') }}</a>
        </div>
    </div>

    @if(!$activeYear)
    <div class="kia-alert kia-alert-danger">{{ __('No active academic year is set — enrollment cannot be shown.') }}</div>
    @endif

    <div class="kia-card">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('Students') }} ({{ $students->count() }})</h2>
        </div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('Student') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Gender') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Attendance') }}</th>
                        <th>{{ __('Average') }}</th>
                        <th>{{ __('Grade') }}</th>
                        <th>{{ __('Rank') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $att  = $attendance->get($student->id);
                            $rate = $att && $att->total > 0 ? round($att->present / $att->total * 100, 1) : null;
                            $result = $results->get($student->id);
                        @endphp
                        <tr>
                            <td>
                                <div class="student-cell">
                                    @if($student->photo)
                                        <img src="{{ route('students.photo', $student) }}" class="student-photo-sm" alt="">
                                    @else
                                        <div class="student-initials">{{ strtoupper(substr($student->name_en, 0, 2)) }}</div>
                                    @endif
                                    <div>
                                        <div style="font-weight:600;">{{ $student->name_km ?: $student->name_en }}</div>
                                        @if($student->name_km)<div style="font-size:.78rem;color:var(--muted);">{{ $student->name_en }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td><span class="mono">{{ $student->student_code }}</span></td>
                            <td>{{ ucfirst($student->gender) }}</td>
                            <td>
                                <span class="pill {{ match($student->status) {
                                    'enrolled'    => 'pill-ok',
                                    'transferred' => 'pill-warn',
                                    'graduated'   => 'pill-royal',
                                    default       => 'pill-bad',
                                } }}">{{ $student->status }}</span>
                            </td>
                            <td>
                                @if($rate === null)
                                    <span style="color:var(--muted);">—</span>
                                @else
                                    <span class="pill {{ $rate >= 80 ? 'pill-ok' : ($rate >= 60 ? 'pill-warn' : 'pill-bad') }}">{{ $rate }}%</span>
                                @endif
                            </td>
                            <td>{{ $result?->average !== null ? number_format($result->average, 1) : '—' }}</td>
                            <td>
                                @if($result)
                                    <span class="pill {{ $result->result === 'pass' ? 'pill-ok' : 'pill-bad' }}">{{ ucfirst($result->result ?? '—') }}</span>
                                @else
                                    <span style="color:var(--muted);">—</span>
                                @endif
                            </td>
                            <td>{{ $result?->rank ?? '—' }}</td>
                            <td class="text-right">
                                <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-ghost">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="kia-empty">
                                    <h3>{{ __('No students enrolled in this section yet.') }}</h3>
                                    @can('students.create')
                                    <a href="{{ route('students.create') }}" class="btn btn-primary">{{ __('Add Student') }}</a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
