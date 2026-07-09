<x-app-layout>
    <x-slot name="title">{{ __('Teacher Dashboard') }}</x-slot>
    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Teacher Dashboard') }}</h1>
    </div>
    <div class="kia-card">
        <div class="kia-card-body">
            <div class="kia-empty">
                <h3>{{ __('Attendance') }}</h3>
                <p>{{ __('Mark attendance for your assigned sections.') }}</p>
                <a href="{{ route('attendance.index') }}" class="btn btn-primary">{{ __('Mark Attendance') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
