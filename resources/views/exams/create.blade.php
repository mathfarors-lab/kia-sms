<x-app-layout>
    <x-slot name="title">New Exam</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">New Exam</h1></div>
        <a href="{{ route('exams.index') }}" class="btn btn-ghost">← Back</a>
    </div>

    <div class="kia-card" style="max-width:520px">
        <form method="POST" action="{{ route('exams.store') }}" class="kia-form">
            @csrf
            <div class="kia-form-group">
                <label class="kia-label">Exam Name *</label>
                <input name="name" value="{{ old('name') }}" class="kia-input @error('name') is-invalid @enderror" placeholder="e.g. Midterm Exam 2025-26">
                @error('name')<span class="kia-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="kia-form-row kia-form-row--2">
                <div class="kia-form-group">
                    <label class="kia-label">Type *</label>
                    <select name="type" class="kia-select @error('type') is-invalid @enderror">
                        <option value="">Select type</option>
                        @foreach(['monthly','midterm','final'] as $t)
                            <option value="{{ $t }}" @selected(old('type') === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                    @error('type')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="kia-form-group">
                    <label class="kia-label">Academic Year *</label>
                    <select name="academic_year_id" class="kia-select @error('academic_year_id') is-invalid @enderror">
                        <option value="">Select year</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" @selected(old('academic_year_id') == $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                    @error('academic_year_id')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="kia-form-row kia-form-row--2">
                <div class="kia-form-group">
                    <label class="kia-label">Semester *</label>
                    <select name="semester" class="kia-select @error('semester') is-invalid @enderror">
                        <option value="">Select semester</option>
                        <option value="1" @selected(old('semester') === '1')>Semester 1</option>
                        <option value="2" @selected(old('semester') === '2')>Semester 2</option>
                    </select>
                    <div style="font-size:.75rem;color:var(--muted);margin-top:4px;">Which term this exam's marks feed into.</div>
                    @error('semester')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="kia-form-group">
                    <label class="kia-label">Weight *</label>
                    <input type="number" name="weight" step="0.01" min="0" value="{{ old('weight', 1) }}" class="kia-input @error('weight') is-invalid @enderror">
                    <div style="font-size:.75rem;color:var(--muted);margin-top:4px;">Relative weight vs other exams in the same semester.</div>
                    @error('weight')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="kia-form-group">
                <label class="kia-label">Exam Date</label>
                <input type="date" name="exam_date" value="{{ old('exam_date') }}" class="kia-input @error('exam_date') is-invalid @enderror">
                <div style="font-size:.75rem;color:var(--muted);margin-top:4px;">Optional — shown on the Academic Calendar once set.</div>
                @error('exam_date')<span class="kia-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="kia-form-actions">
                <button type="submit" class="btn btn-primary">Create Exam</button>
                <a href="{{ route('exams.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
