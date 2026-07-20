<x-app-layout>
    <x-slot name="title">{{ __('academic_calendar.edit_holiday') }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('academic_calendar.edit_holiday') }}</h1>
        <a href="{{ route('holidays.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    <div class="kia-card" style="max-width:520px">
        <form method="POST" action="{{ route('holidays.update', $holiday) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">{{ __('academic_calendar.holiday_name') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $holiday->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('academic_calendar.start_date') }} <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $holiday->start_date->toDateString()) }}" required>
                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('academic_calendar.end_date') }} <span class="text-danger">*</span></label>
                <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $holiday->end_date->toDateString()) }}" required>
                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                <a href="{{ route('holidays.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
