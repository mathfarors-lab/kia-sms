<x-app-layout>
    <x-slot name="title">{{ __('Edit Student') }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('students.index') }}">{{ __('Students') }}</a>
        <span class="sep">/</span>
        <a href="{{ route('students.show', $student) }}">{{ $student->name_en }}</a>
        <span class="sep">/</span>
        <span>{{ __('Edit') }}</span>
    </div>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Edit Student') }}</h1>
    </div>

    <div class="kia-card" style="max-width:760px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ $student->name_en }} <span class="mono" style="font-size:.85rem;color:var(--muted);">{{ $student->student_code }}</span></h2>
        </div>
        <div class="kia-card-body">
            <form method="POST" action="{{ route('students.update', $student) }}" enctype="multipart/form-data">
                @csrf @method('PATCH')

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="name_en">{{ __('Full Name (English)') }} <span class="req">*</span></label>
                        <input type="text" id="name_en" name="name_en" class="form-control {{ $errors->has('name_en') ? 'is-invalid' : '' }}"
                               value="{{ old('name_en', $student->name_en) }}" required>
                        @error('name_en')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="name_km">{{ __('Full Name (Khmer)') }}</label>
                        <input type="text" id="name_km" name="name_km" class="form-control"
                               value="{{ old('name_km', $student->name_km) }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="gender">{{ __('Gender') }} <span class="req">*</span></label>
                        <select id="gender" name="gender" class="form-control" required>
                            @foreach(['male','female','other'] as $g)
                            <option value="{{ $g }}" {{ old('gender', $student->gender) == $g ? 'selected' : '' }}>{{ ucfirst($g) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="date_of_birth">{{ __('Date of Birth') }}</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                               value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">{{ __('Status') }} <span class="req">*</span></label>
                        <select id="status" name="status" class="form-control" required>
                            @foreach(['enrolled','transferred','graduated','dropped'] as $s)
                            <option value="{{ $s }}" {{ old('status', $student->status) == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="section_id">{{ __('Class / Section') }}</label>
                        <select id="section_id" name="section_id" class="form-control">
                            <option value="">{{ __('— Not assigned yet —') }}</option>
                            @foreach($sections as $section)
                            <option value="{{ $section->id }}" {{ old('section_id', $currentSectionId) == $section->id ? 'selected' : '' }}>
                                {{ $section->schoolClass?->name }} - {{ $section->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="photo">{{ __('Photo') }}</label>
                        @if($student->photo)
                        <div style="margin-bottom:8px;">
                            <img src="{{ route('students.photo', $student) }}" class="photo-preview" alt="Current photo">
                            <div style="font-size:.75rem;color:var(--muted);margin-top:4px;">{{ __('Current photo') }}</div>
                        </div>
                        @endif
                        <input type="file" id="photo" name="photo" class="form-control" accept="image/*" onchange="previewPhoto(this)">
                        <div id="photoPreviewWrap" style="margin-top:10px;display:none;">
                            <img id="photoPreview" class="photo-preview" src="" alt="New photo">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">{{ __('Address') }}</label>
                    <textarea id="address" name="address" class="form-control" rows="3">{{ old('address', $student->address) }}</textarea>
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:8px;">
                    <a href="{{ route('students.show', $student) }}" class="btn btn-outline">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
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
