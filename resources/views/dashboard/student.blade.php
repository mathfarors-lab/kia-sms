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
                    <img src="{{ route('students.photo', $student) }}" class="photo-preview">
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
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('Quick Links') }}</h2>
        </div>
        <div class="kia-card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="{{ route('student.attendance') }}" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9,11 12,14 22,4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                {{ __('My Attendance') }}
            </a>
            <a href="{{ route('invoices.index') }}" class="btn btn-outline">{{ __('My Invoices') }}</a>
            <a href="{{ route('announcements.index') }}" class="btn btn-ghost">{{ __('Announcements') }}</a>
        </div>
    </div>
</x-app-layout>
