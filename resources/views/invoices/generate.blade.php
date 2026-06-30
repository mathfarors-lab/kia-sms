<x-app-layout>
    <x-slot name="title">Generate Invoices</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Generate Invoices</h1></div>
        <a href="{{ route('invoices.index') }}" class="btn btn-ghost">← Back</a>
    </div>

    <div class="kia-card" style="max-width:520px">
        <form method="POST" action="{{ route('invoices.generate') }}" class="kia-form">
            @csrf
            <div class="kia-form-group">
                <label class="kia-label">Class *</label>
                <select name="school_class_id" class="kia-select @error('school_class_id') is-invalid @enderror">
                    <option value="">Select class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(old('school_class_id') == $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
                @error('school_class_id')<span class="kia-field-error">{{ $message }}</span>@enderror
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
            <div class="kia-form-group">
                <label class="kia-label">Term * <small style="color:var(--muted)">(e.g. term_1, monthly_2025_10)</small></label>
                <input name="term" value="{{ old('term') }}" class="kia-input @error('term') is-invalid @enderror" placeholder="term_1">
                @error('term')<span class="kia-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="kia-form-group">
                <label class="kia-label">Due Date</label>
                <input name="due_date" type="date" value="{{ old('due_date') }}" class="kia-input">
            </div>
            <div class="kia-alert" style="background:var(--paper);border:1px solid var(--line);border-radius:6px;padding:.75rem 1rem;font-size:.85rem;color:var(--muted)">
                Re-running generation for the same class + term is safe — existing invoices are skipped.
            </div>
            <div class="kia-form-actions" style="margin-top:1rem">
                <button type="submit" class="btn btn-primary">Generate</button>
                <a href="{{ route('invoices.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
