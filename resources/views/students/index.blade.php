<x-app-layout>
    <x-slot name="title">{{ __('Students') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('Students') }}</h1>
            <p class="kia-page-sub">{{ $students->total() }} {{ __('total') }}</p>
        </div>
        @can('students.create')
        <a href="{{ route('students.create') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ __('Add Student') }}
        </a>
        @endcan
    </div>

    {{-- Filters --}}
    <form method="GET" class="kia-filter-bar">
        <input type="search" name="search" class="form-control" placeholder="{{ __('Search name or code…') }}" value="{{ request('search') }}">
        <select name="status" class="form-control" style="min-width:140px;">
            <option value="">{{ __('All statuses') }}</option>
            @foreach(['enrolled','transferred','graduated','dropped'] as $s)
            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <select name="gender" class="form-control" style="min-width:120px;">
            <option value="">{{ __('All genders') }}</option>
            <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>{{ __('Male') }}</option>
            <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>{{ __('Female') }}</option>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">{{ __('Filter') }}</button>
        @if(request()->hasAny(['search','status','gender']))
        <a href="{{ route('students.index') }}" class="btn btn-ghost btn-sm">{{ __('Clear') }}</a>
        @endif
    </form>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('Student') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Gender') }}</th>
                        <th>{{ __('Date of Birth') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    <tr>
                        <td>
                            <div class="student-cell">
                                @if($student->photo)
                                    <img src="{{ Storage::url($student->photo) }}" class="student-photo-sm" alt="">
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
                        <td>{{ $student->date_of_birth?->format('d M Y') ?? '—' }}</td>
                        <td>
                            <span class="pill {{ match($student->status) {
                                'enrolled'    => 'pill-ok',
                                'transferred' => 'pill-warn',
                                'graduated'   => 'pill-royal',
                                default       => 'pill-bad',
                            } }}">{{ $student->status }}</span>
                        </td>
                        <td style="text-align:right;">
                            <div class="d-flex gap-2" style="justify-content:flex-end;">
                                <a href="{{ route('students.show', $student) }}" class="btn btn-ghost btn-sm">{{ __('View') }}</a>
                                @can('students.edit')
                                <a href="{{ route('students.edit', $student) }}" class="btn btn-outline btn-sm">{{ __('Edit') }}</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="kia-empty">
                                <h3>{{ __('No students found') }}</h3>
                                @can('students.create')
                                <a href="{{ route('students.create') }}" class="btn btn-primary">{{ __('Add First Student') }}</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())
        <div class="kia-pagination">
            {{ $students->links('vendor.pagination.kia') }}
        </div>
        @endif
    </div>
</x-app-layout>
