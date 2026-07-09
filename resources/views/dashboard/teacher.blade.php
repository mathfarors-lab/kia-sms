<x-app-layout>
    <x-slot name="title">{{ __('Teacher Dashboard') }}</x-slot>
    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Teacher Dashboard') }}</h1>
    </div>

    {{-- My Sections --}}
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('staff_dashboard.my_sections') }}</h2>
        </div>
        <div class="kia-card-body">
            @forelse($sections as $section)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.6rem 0;border-bottom:1px solid var(--border);">
                    <div>
                        <strong>{{ $section->schoolClass->name }} {{ $section->name }}</strong>
                        <span style="color:var(--muted);font-size:.85rem;">— {{ __('staff_dashboard.students_count', ['count' => $section->students_count]) }}</span>
                    </div>
                    @if($section->attendance_marked_today)
                        <span class="kia-badge" style="background:var(--success-light,#d1fae5);color:var(--success,#065f46)">✓ {{ __('staff_dashboard.attendance_marked') }}</span>
                    @else
                        <a href="{{ route('attendance.mark', $section) }}" class="btn btn-sm btn-primary">{{ __('staff_dashboard.mark_now') }}</a>
                    @endif
                </div>
            @empty
                <p style="color:var(--muted);">{{ __('staff_dashboard.no_sections_yet') }}</p>
            @endforelse
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        {{-- Today's Timetable --}}
        <div class="kia-card">
            <div class="kia-card-header">
                <h2 class="kia-card-title">{{ __('staff_dashboard.my_timetable_today') }}</h2>
            </div>
            <div class="kia-card-body">
                @forelse($todayTimetable as $slot)
                    <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid var(--border);font-size:.9rem;">
                        <span><strong>P{{ $slot->period }}</strong> {{ $slot->subject->name_en }} — {{ $slot->section->schoolClass->name }} {{ $slot->section->name }}</span>
                        <span style="color:var(--muted);">{{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('H:i') }}</span>
                    </div>
                @empty
                    <p style="color:var(--muted);">{{ __('staff_dashboard.no_classes_today') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Pending grading --}}
        <div class="kia-card">
            <div class="kia-card-header">
                <h2 class="kia-card-title">{{ __('staff_dashboard.pending_to_grade') }}</h2>
            </div>
            <div class="kia-card-body" style="text-align:center;">
                <div style="font-size:2.5rem;font-weight:700;{{ $pendingGradeCount > 0 ? 'color:var(--warning,#d97706)' : '' }}">
                    {{ $pendingGradeCount }}
                </div>
                <a href="{{ route('homework.index') }}" class="btn btn-outline" style="margin-top:.75rem;">{{ __('staff_dashboard.grade_now') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
