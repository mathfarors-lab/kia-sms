<x-app-layout>
    <x-slot name="title">Edit Class</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">Edit Class</h1>
        <a href="{{ route('classes.index') }}" class="btn btn-ghost">Back</a>
    </div>

    <div class="kia-card" style="max-width:560px">
        <form method="POST" action="{{ route('classes.update', $class) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $class->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Level</label>
                <input type="text" name="level" class="form-control" value="{{ old('level', $class->level) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Capacity</label>
                <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $class->capacity) }}" min="1">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('promotion.next_class') }}</label>
                <select name="next_class_id" class="form-control">
                    <option value="">{{ __('promotion.next_class_none') }}</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" @selected(old('next_class_id', $class->next_class_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('classes.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
