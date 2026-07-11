{{-- Shared applicant form fields; $application is null on create --}}
<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="name_en">{{ __('Full Name (English)') }} <span class="req">*</span></label>
        <input type="text" id="name_en" name="name_en" class="form-control {{ $errors->has('name_en') ? 'is-invalid' : '' }}"
               value="{{ old('name_en', $application?->name_en) }}" required>
        @error('name_en')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="name_km">{{ __('Full Name (Khmer)') }}</label>
        <input type="text" id="name_km" name="name_km" class="form-control"
               value="{{ old('name_km', $application?->name_km) }}">
    </div>

    <div class="form-group">
        <label class="form-label" for="gender">{{ __('Gender') }} <span class="req">*</span></label>
        <select id="gender" name="gender" class="form-control" required>
            @foreach(['male','female','other'] as $g)
            <option value="{{ $g }}" @selected(old('gender', $application?->gender) === $g)>{{ ucfirst($g) }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="date_of_birth">{{ __('Date of Birth') }}</label>
        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control {{ $errors->has('date_of_birth') ? 'is-invalid' : '' }}"
               value="{{ old('date_of_birth', $application?->date_of_birth?->format('Y-m-d')) }}">
        @error('date_of_birth')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="guardian_name">{{ __('admissions.guardian') }}</label>
        <input type="text" id="guardian_name" name="guardian_name" class="form-control"
               value="{{ old('guardian_name', $application?->guardian_name) }}">
    </div>

    <div class="form-group">
        <label class="form-label" for="guardian_phone">{{ __('admissions.guardian_phone') }}</label>
        <input type="text" id="guardian_phone" name="guardian_phone" class="form-control"
               value="{{ old('guardian_phone', $application?->guardian_phone) }}">
    </div>

    <div class="form-group">
        <label class="form-label" for="guardian_relation">{{ __('admissions.guardian_relation') }}</label>
        <input type="text" id="guardian_relation" name="guardian_relation" class="form-control"
               value="{{ old('guardian_relation', $application?->guardian_relation) }}" placeholder="father / mother / uncle …">
    </div>

    <div class="form-group">
        <label class="form-label" for="desired_class_id">{{ __('admissions.desired_class') }}</label>
        <select id="desired_class_id" name="desired_class_id" class="form-control">
            <option value="">—</option>
            @foreach($classes as $class)
            <option value="{{ $class->id }}" @selected(old('desired_class_id', $application?->desired_class_id) == $class->id)>{{ $class->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="academic_year_id">{{ __('admissions.academic_year') }}</label>
        <select id="academic_year_id" name="academic_year_id" class="form-control">
            <option value="">—</option>
            @foreach($years as $year)
            <option value="{{ $year->id }}" @selected(old('academic_year_id', $application?->academic_year_id) == $year->id)>{{ $year->name }}</option>
            @endforeach
        </select>
    </div>

    @if(!$application)
    <div class="form-group">
        <label class="form-label" for="status">{{ __('admissions.status') }} <span class="req">*</span></label>
        <select id="status" name="status" class="form-control" required>
            <option value="enquiry" @selected(old('status') === 'enquiry')>{{ __('admissions.status_enquiry') }}</option>
            <option value="applied" @selected(old('status') === 'applied')>{{ __('admissions.status_applied') }}</option>
        </select>
    </div>
    @endif

    <div class="form-group">
        <label class="form-label" for="document">{{ __('admissions.document') }}</label>
        @if($application?->document_path)
        <div style="margin-bottom:6px;font-size:.82rem;">
            <a href="{{ route('admissions.document', $application) }}">{{ $application->document_original_name ?? __('admissions.download_document') }}</a>
        </div>
        @endif
        <input type="file" id="document" name="document" class="form-control {{ $errors->has('document') ? 'is-invalid' : '' }}"
               accept=".pdf,.jpg,.jpeg,.png">
        @error('document')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>
</div>

<div class="form-group">
    <label class="form-label" for="address">{{ __('Address') }}</label>
    <textarea id="address" name="address" class="form-control" rows="2">{{ old('address', $application?->address) }}</textarea>
</div>

<div class="form-group">
    <label class="form-label" for="notes">{{ __('admissions.notes') }}</label>
    <textarea id="notes" name="notes" class="form-control" rows="3">{{ old('notes', $application?->notes) }}</textarea>
</div>
