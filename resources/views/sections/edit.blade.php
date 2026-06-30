<x-app-layout>
    <x-slot name="title">Edit Section</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">Edit Section — {{ $class->name }}</h1>
        <a href="{{ route('classes.sections.index', $class) }}" class="btn btn-ghost">Back</a>
    </div>

    <div class="kia-card" style="max-width:560px">
        <form method="POST" action="{{ route('classes.sections.update', [$class, $section]) }}">
            @csrf @method('PUT')
            <input type="hidden" name="school_class_id" value="{{ $class->id }}">
            <div class="form-group">
                <label class="form-label">Section Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $section->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Class Teacher</label>
                <select name="class_teacher_id" class="form-control">
                    <option value="">— None —</option>
                    @foreach($staffList as $staff)
                    <option value="{{ $staff->id }}" {{ old('class_teacher_id', $section->class_teacher_id) == $staff->id ? 'selected' : '' }}>
                        {{ $staff->user->name }} ({{ $staff->position ?? $staff->staff_code }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('classes.sections.index', $class) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
