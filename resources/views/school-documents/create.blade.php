<x-app-layout>
    <x-slot name="title">{{ __('school_documents.upload_document') }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('school_documents.upload_document') }}</h1>
        <a href="{{ route('school-documents.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    <div class="kia-card" style="max-width:520px">
        <form method="POST" action="{{ route('school-documents.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">{{ __('school_documents.title') }} <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('school_documents.category') }} <span class="text-danger">*</span></label>
                <select name="category" class="form-control @error('category') is-invalid @enderror" required>
                    <option value="">—</option>
                    @foreach(\App\Models\SchoolDocument::CATEGORIES as $cat)
                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ __('school_documents.category_'.$cat) }}</option>
                    @endforeach
                </select>
                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('school_documents.file') }} <span class="text-danger">*</span></label>
                <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" required>
                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="all_branches" value="1" {{ old('all_branches') ? 'checked' : '' }}>
                    {{ __('school_documents.all_branches') }}
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">{{ __('school_documents.upload_document') }}</button>
                <a href="{{ route('school-documents.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
