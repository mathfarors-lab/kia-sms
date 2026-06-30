<x-app-layout>
    <x-slot name="title">{{ __('Student Dashboard') }}</x-slot>
    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('My Dashboard') }}</h1>
    </div>
    @if($student)
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-body">
            <div class="d-flex align-center gap-3">
                @if($student->photo)
                    <img src="{{ Storage::url($student->photo) }}" class="photo-preview">
                @else
                    <div class="photo-placeholder">👤</div>
                @endif
                <div>
                    <div style="font-weight:700;font-size:1.1rem;">{{ $student->name_km ?: $student->name_en }}</div>
                    <div style="color:var(--muted);font-size:.85rem;">{{ $student->name_en }}</div>
                    <span class="mono" style="font-size:.8rem;color:var(--royal);">{{ $student->student_code }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif
    <div class="kia-card">
        <div class="kia-card-body">
            <div class="kia-empty">
                <h3>{{ __('Attendance & Grades') }}</h3>
                <p>{{ __('Available in Phase 2 & 3.') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
