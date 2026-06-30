<x-app-layout>
    <x-slot name="title">Edit Scholarship</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Edit Scholarship</h1></div>
        <a href="{{ route('scholarships.index') }}" class="btn btn-ghost">← Back</a>
    </div>

    <div class="kia-card" style="max-width:520px">
        <form method="POST" action="{{ route('scholarships.update', $scholarship) }}" class="kia-form">
            @csrf @method('PUT')
            <div class="kia-form-group">
                <label class="kia-label">Student *</label>
                <select name="student_id" class="kia-select">
                    @foreach($students as $s)
                        <option value="{{ $s->id }}" @selected(old('student_id', $scholarship->student_id) == $s->id)>{{ $s->name_en }}</option>
                    @endforeach
                </select>
            </div>
            <div class="kia-form-row kia-form-row--2">
                <div class="kia-form-group">
                    <label class="kia-label">Discount Type *</label>
                    <select name="type" class="kia-select">
                        <option value="percent" @selected(old('type', $scholarship->type) === 'percent')>Percentage (%)</option>
                        <option value="fixed" @selected(old('type', $scholarship->type) === 'fixed')>Fixed Amount ($)</option>
                    </select>
                </div>
                <div class="kia-form-group">
                    <label class="kia-label">Value *</label>
                    <input name="value" type="number" step="0.01" min="0.01" value="{{ old('value', $scholarship->value) }}" class="kia-input">
                </div>
            </div>
            <div class="kia-form-group">
                <label class="kia-label">Reason</label>
                <input name="reason" value="{{ old('reason', $scholarship->reason) }}" class="kia-input">
            </div>
            <div class="kia-form-group">
                <label class="kia-checkbox-label">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $scholarship->is_active))> Active
                </label>
            </div>
            <div class="kia-form-actions">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('scholarships.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
