<x-app-layout>
    <x-slot name="title">{{ __('Add Student') }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('students.index') }}">{{ __('Students') }}</a>
        <span class="sep">/</span>
        <span>{{ __('Add Student') }}</span>
    </div>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Add New Student') }}</h1>
    </div>

    <div class="kia-card" style="max-width:760px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('Student Information') }}</h2>
        </div>
        <div class="kia-card-body">
            <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="name_en">{{ __('Full Name (English)') }} <span class="req">*</span></label>
                        <input type="text" id="name_en" name="name_en" class="form-control {{ $errors->has('name_en') ? 'is-invalid' : '' }}"
                               value="{{ old('name_en') }}" required>
                        @error('name_en')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="name_km">{{ __('Full Name (Khmer)') }}</label>
                        <input type="text" id="name_km" name="name_km" class="form-control {{ $errors->has('name_km') ? 'is-invalid' : '' }}"
                               value="{{ old('name_km') }}">
                        @error('name_km')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="gender">{{ __('Gender') }} <span class="req">*</span></label>
                        <select id="gender" name="gender" class="form-control {{ $errors->has('gender') ? 'is-invalid' : '' }}" required>
                            <option value="">{{ __('Select…') }}</option>
                            <option value="male"   {{ old('gender') == 'male'   ? 'selected' : '' }}>{{ __('Male') }}</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                            <option value="other"  {{ old('gender') == 'other'  ? 'selected' : '' }}>{{ __('Other') }}</option>
                        </select>
                        @error('gender')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="date_of_birth">{{ __('Date of Birth') }}</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control {{ $errors->has('date_of_birth') ? 'is-invalid' : '' }}"
                               value="{{ old('date_of_birth') }}">
                        @error('date_of_birth')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">{{ __('Status') }} <span class="req">*</span></label>
                        <select id="status" name="status" class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" required>
                            <option value="enrolled" {{ old('status', 'enrolled') == 'enrolled' ? 'selected' : '' }}>{{ __('Enrolled') }}</option>
                            <option value="transferred" {{ old('status') == 'transferred' ? 'selected' : '' }}>{{ __('Transferred') }}</option>
                            <option value="graduated"   {{ old('status') == 'graduated' ? 'selected' : '' }}>{{ __('Graduated') }}</option>
                            <option value="dropped"     {{ old('status') == 'dropped' ? 'selected' : '' }}>{{ __('Dropped') }}</option>
                        </select>
                        @error('status')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="section_id">{{ __('Class / Section') }}</label>
                        <select id="section_id" name="section_id" class="form-control {{ $errors->has('section_id') ? 'is-invalid' : '' }}">
                            <option value="">{{ __('— Not assigned yet —') }}</option>
                            @foreach($sections as $section)
                            <option value="{{ $section->id }}" {{ old('section_id') == $section->id ? 'selected' : '' }}>
                                {{ $section->schoolClass?->name }} - {{ $section->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('section_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="photo">{{ __('Photo') }}</label>
                        <input type="file" id="photo" name="photo" class="form-control {{ $errors->has('photo') ? 'is-invalid' : '' }}"
                               accept="image/*" onchange="previewPhoto(this)">
                        @error('photo')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        <div id="photoPreviewWrap" style="margin-top:10px;display:none;">
                            <img id="photoPreview" class="photo-preview" src="" alt="Preview">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">{{ __('Address') }}</label>
                    <textarea id="address" name="address" class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}"
                              rows="3">{{ old('address') }}</textarea>
                    @error('address')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:8px;">
                    <a href="{{ route('students.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Create Student') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('photoPreview').src = e.target.result;
            document.getElementById('photoPreviewWrap').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
