<x-app-layout>
    <x-slot name="title">{{ __('Edit Staff') }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('staff.index') }}">{{ __('Staff') }}</a>
        <span class="sep">/</span>
        <a href="{{ route('staff.show', $staff) }}">{{ $staff->user->name }}</a>
        <span class="sep">/</span>
        <span>{{ __('Edit') }}</span>
    </div>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Edit Staff Member') }}</h1>
    </div>

    <div class="kia-card" style="max-width:720px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('staff.update', $staff) }}" enctype="multipart/form-data">
                @csrf @method('PATCH')
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">{{ __('Full Name') }} <span class="req">*</span></label>
                        <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                               value="{{ old('name', $staff->user->name) }}" required>
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Email') }} <span class="req">*</span></label>
                        <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                               value="{{ old('email', $staff->user->email) }}" required>
                        @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Phone') }}</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $staff->user->phone) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Role') }} <span class="req">*</span></label>
                        <select name="role" class="form-control" required>
                            @foreach($roles as $role)
                            <option value="{{ $role }}" {{ old('role', $staff->user->getRoleNames()->first()) == $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Position') }}</label>
                        <input type="text" name="position" class="form-control" value="{{ old('position', $staff->position) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Department') }}</label>
                        <input type="text" name="department" class="form-control" value="{{ old('department', $staff->department) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Join Date') }}</label>
                        <input type="date" name="joined_at" class="form-control" value="{{ old('joined_at', $staff->joined_at?->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Salary (USD)') }}</label>
                        <input type="number" name="salary" class="form-control" step="0.01" min="0" value="{{ old('salary', $staff->salary) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('hr.contract_type') }}</label>
                        <select name="contract_type" class="form-control {{ $errors->has('contract_type') ? 'is-invalid' : '' }}">
                            <option value="">—</option>
                            @foreach(\App\Models\Staff::CONTRACT_TYPES as $type)
                            <option value="{{ $type }}" {{ old('contract_type', $staff->contract_type) === $type ? 'selected' : '' }}>{{ __('hr.contract_type_'.$type) }}</option>
                            @endforeach
                        </select>
                        @error('contract_type')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('hr.contract_end_date') }}</label>
                        <input type="date" name="contract_end_date" class="form-control {{ $errors->has('contract_end_date') ? 'is-invalid' : '' }}" value="{{ old('contract_end_date', $staff->contract_end_date?->format('Y-m-d')) }}">
                        @error('contract_end_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('hr.employment_status') }}</label>
                        <select name="employment_status" class="form-control">
                            @foreach(\App\Models\Staff::EMPLOYMENT_STATUSES as $status)
                            <option value="{{ $status }}" {{ old('employment_status', $staff->employment_status) === $status ? 'selected' : '' }}>{{ __('hr.employment_status_'.$status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="photo">{{ __('Photo') }}</label>
                        @if($staff->photo)
                        <div style="margin-bottom:8px;">
                            <img src="{{ route('staff.photo', $staff) }}" class="photo-preview" alt="Current photo">
                            <div style="font-size:.75rem;color:var(--muted);margin-top:4px;">{{ __('Current photo') }}</div>
                        </div>
                        @endif
                        <input type="file" id="photo" name="photo" class="form-control {{ $errors->has('photo') ? 'is-invalid' : '' }}"
                               accept="image/*" onchange="previewPhoto(this)">
                        @error('photo')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        <div id="photoPreviewWrap" style="margin-top:10px;display:none;">
                            <img id="photoPreview" class="photo-preview" src="" alt="New photo">
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:12px;padding-top:8px;">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    <a href="{{ route('staff.show', $staff) }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
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
