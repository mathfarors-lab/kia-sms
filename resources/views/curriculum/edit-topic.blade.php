<x-app-layout>
    <x-slot name="title">{{ __('curriculum.edit_topic') }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('curriculum.edit_topic') }}</h1>
        <a href="{{ route('curriculum.show', $topic->classSubject) }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    <div class="kia-card" style="max-width:560px">
        <form method="POST" action="{{ route('curriculum-topics.update', $topic) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">{{ __('curriculum.topic_title') }} <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $topic->title) }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('curriculum.description') }}</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description', $topic->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">#</label>
                <input type="number" name="sequence" class="form-control" min="0" value="{{ old('sequence', $topic->sequence) }}">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                <a href="{{ route('curriculum.show', $topic->classSubject) }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
