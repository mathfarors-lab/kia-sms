<x-app-layout>
    <x-slot name="title">New Academic Year</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">New Academic Year</h1>
        <a href="{{ route('academic-years.index') }}" class="btn btn-ghost">Back</a>
    </div>

    <div class="kia-card" style="max-width:560px">
        <form method="POST" action="{{ route('academic-years.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">End Date <span class="text-danger">*</span></label>
                <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}>
                    Set as active year
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create</button>
                <a href="{{ route('academic-years.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
