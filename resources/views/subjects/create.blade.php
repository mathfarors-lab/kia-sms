<x-app-layout>
    <x-slot name="title">New Subject</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">New Subject</h1>
        <a href="{{ route('subjects.index') }}" class="btn btn-ghost">Back</a>
    </div>

    <div class="kia-card" style="max-width:560px">
        <form method="POST" action="{{ route('subjects.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Name (English) <span class="text-danger">*</span></label>
                <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror" value="{{ old('name_en') }}" required>
                @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Name (Khmer)</label>
                <input type="text" name="name_km" class="form-control" value="{{ old('name_km') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Code <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g. MATH101" required>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Full Mark</label>
                <input type="number" name="full_mark" class="form-control" value="{{ old('full_mark', 100) }}" min="1">
            </div>
            <div class="form-group">
                <label class="form-label">Coefficient</label>
                <input type="number" name="coefficient" step="0.01" min="0.01" max="99.99" class="form-control @error('coefficient') is-invalid @enderror" value="{{ old('coefficient', 1.00) }}">
                <div style="font-size:.75rem;color:var(--muted);margin-top:4px;">Relative weight of this subject when averaging grades. Leave at 1.00 for equal weight.</div>
                @error('coefficient')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create</button>
                <a href="{{ route('subjects.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
