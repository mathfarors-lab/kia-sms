<x-app-layout>
    <x-slot name="title">Edit Grade Scale</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Edit Grade Scale — {{ $gradeScale->grade }}</h1>
        </div>
        <a href="{{ route('grade-scales.index') }}" class="btn btn-ghost">← Back</a>
    </div>

    <div class="kia-card" style="max-width:520px">
        <form method="POST" action="{{ route('grade-scales.update', $gradeScale) }}" class="kia-form">
            @csrf @method('PUT')
            <div class="kia-form-row kia-form-row--2">
                <div class="kia-form-group">
                    <label class="kia-label">Grade *</label>
                    <input name="grade" value="{{ old('grade', $gradeScale->grade) }}" class="kia-input @error('grade') is-invalid @enderror" maxlength="5">
                    @error('grade')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="kia-form-group">
                    <label class="kia-label">GPA *</label>
                    <input name="gpa" type="number" step="0.01" min="0" max="4" value="{{ old('gpa', $gradeScale->gpa) }}" class="kia-input @error('gpa') is-invalid @enderror">
                    @error('gpa')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="kia-form-row kia-form-row--2">
                <div class="kia-form-group">
                    <label class="kia-label">Min Score *</label>
                    <input name="min_score" type="number" step="0.01" min="0" max="100" value="{{ old('min_score', $gradeScale->min_score) }}" class="kia-input @error('min_score') is-invalid @enderror">
                    @error('min_score')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="kia-form-group">
                    <label class="kia-label">Max Score *</label>
                    <input name="max_score" type="number" step="0.01" min="0" max="100" value="{{ old('max_score', $gradeScale->max_score) }}" class="kia-input @error('max_score') is-invalid @enderror">
                    @error('max_score')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="kia-form-group">
                <label class="kia-label">Remark (English) *</label>
                <input name="remark_en" value="{{ old('remark_en', $gradeScale->remark_en) }}" class="kia-input @error('remark_en') is-invalid @enderror">
                @error('remark_en')<span class="kia-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="kia-form-group">
                <label class="kia-label">Remark (Khmer) *</label>
                <input name="remark_km" value="{{ old('remark_km', $gradeScale->remark_km) }}" class="kia-input @error('remark_km') is-invalid @enderror">
                @error('remark_km')<span class="kia-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="kia-form-actions">
                <button type="submit" class="btn btn-primary">Update Grade Scale</button>
                <a href="{{ route('grade-scales.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
