<x-app-layout>
    <x-slot name="title">New Class</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">New Class</h1>
        <a href="{{ route('classes.index') }}" class="btn btn-ghost">Back</a>
    </div>

    <div class="kia-card" style="max-width:560px">
        <form method="POST" action="{{ route('classes.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Level</label>
                <input type="text" name="level" class="form-control" value="{{ old('level') }}" placeholder="e.g. High School">
            </div>
            <div class="form-group">
                <label class="form-label">Capacity</label>
                <input type="number" name="capacity" class="form-control" value="{{ old('capacity', 30) }}" min="1">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create</button>
                <a href="{{ route('classes.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
