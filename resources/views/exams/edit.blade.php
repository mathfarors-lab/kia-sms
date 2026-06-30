<x-app-layout>
    <x-slot name="title">Edit Exam</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Edit Exam</h1></div>
        <a href="{{ route('exams.index') }}" class="btn btn-ghost">← Back</a>
    </div>

    <div class="kia-card" style="max-width:520px">
        <form method="POST" action="{{ route('exams.update', $exam) }}" class="kia-form">
            @csrf @method('PUT')
            <div class="kia-form-group">
                <label class="kia-label">Exam Name *</label>
                <input name="name" value="{{ old('name', $exam->name) }}" class="kia-input @error('name') is-invalid @enderror">
                @error('name')<span class="kia-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="kia-form-row kia-form-row--2">
                <div class="kia-form-group">
                    <label class="kia-label">Type *</label>
                    <select name="type" class="kia-select @error('type') is-invalid @enderror">
                        @foreach(['monthly','midterm','final'] as $t)
                            <option value="{{ $t }}" @selected(old('type', $exam->type) === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                    @error('type')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="kia-form-group">
                    <label class="kia-label">Academic Year *</label>
                    <select name="academic_year_id" class="kia-select @error('academic_year_id') is-invalid @enderror">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" @selected(old('academic_year_id', $exam->academic_year_id) == $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                    @error('academic_year_id')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="kia-form-actions">
                <button type="submit" class="btn btn-primary">Update Exam</button>
                <a href="{{ route('exams.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
