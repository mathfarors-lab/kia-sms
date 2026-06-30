<x-app-layout>
    <x-slot name="title">Edit Subject</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">Edit Subject</h1>
        <a href="{{ route('subjects.index') }}" class="btn btn-ghost">Back</a>
    </div>

    <div class="kia-card" style="max-width:560px">
        <form method="POST" action="{{ route('subjects.update', $subject) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Name (English) <span class="text-danger">*</span></label>
                <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror" value="{{ old('name_en', $subject->name_en) }}" required>
                @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Name (Khmer)</label>
                <input type="text" name="name_km" class="form-control" value="{{ old('name_km', $subject->name_km) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Code <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $subject->code) }}" required>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Full Mark</label>
                <input type="number" name="full_mark" class="form-control" value="{{ old('full_mark', $subject->full_mark) }}" min="1">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('subjects.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
