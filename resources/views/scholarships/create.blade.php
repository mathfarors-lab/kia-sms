<x-app-layout>
    <x-slot name="title">Add Scholarship</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Add Scholarship</h1></div>
        <a href="{{ route('scholarships.index') }}" class="btn btn-ghost">← Back</a>
    </div>

    <div class="kia-card" style="max-width:520px">
        <form method="POST" action="{{ route('scholarships.store') }}" class="kia-form">
            @csrf
            <div class="kia-form-group">
                <label class="kia-label">Student *</label>
                <select name="student_id" class="kia-select @error('student_id') is-invalid @enderror">
                    <option value="">Select student</option>
                    @foreach($students as $s)
                        <option value="{{ $s->id }}" @selected(old('student_id') == $s->id)>{{ $s->name_en }}</option>
                    @endforeach
                </select>
                @error('student_id')<span class="kia-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="kia-form-row kia-form-row--2">
                <div class="kia-form-group">
                    <label class="kia-label">Discount Type *</label>
                    <select name="type" class="kia-select @error('type') is-invalid @enderror">
                        <option value="percent" @selected(old('type') === 'percent')>Percentage (%)</option>
                        <option value="fixed" @selected(old('type') === 'fixed')>Fixed Amount ($)</option>
                    </select>
                    @error('type')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="kia-form-group">
                    <label class="kia-label">Value *</label>
                    <input name="value" type="number" step="0.01" min="0.01" value="{{ old('value') }}" class="kia-input @error('value') is-invalid @enderror">
                    @error('value')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="kia-form-group">
                <label class="kia-label">Reason</label>
                <input name="reason" value="{{ old('reason') }}" class="kia-input" placeholder="Merit scholarship, hardship, etc.">
            </div>
            <div class="kia-form-group">
                <label class="kia-checkbox-label">
                    <input type="checkbox" name="is_active" value="1" @checked(true)> Active
                </label>
            </div>
            <div class="kia-form-actions">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('scholarships.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
