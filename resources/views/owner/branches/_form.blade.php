{{-- Shared branch form fields; $branch is null on create --}}
<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="name_en">{{ __('branches.name_en') }} <span class="req">*</span></label>
        <input type="text" id="name_en" name="name_en" class="form-control {{ $errors->has('name_en') ? 'is-invalid' : '' }}"
               value="{{ old('name_en', $branch?->name_en) }}" required>
        @error('name_en')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="name_km">{{ __('branches.name_km') }}</label>
        <input type="text" id="name_km" name="name_km" class="form-control"
               value="{{ old('name_km', $branch?->name_km) }}">
    </div>

    <div class="form-group">
        <label class="form-label" for="code">{{ __('branches.code') }} <span class="req">*</span></label>
        <input type="text" id="code" name="code" class="form-control mono {{ $errors->has('code') ? 'is-invalid' : '' }}"
               value="{{ old('code', $branch?->code) }}" maxlength="10" style="text-transform:uppercase;" required>
        <div style="font-size:.75rem;color:var(--muted);margin-top:4px;">{{ __('branches.code_hint') }}</div>
        @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="logo">{{ __('branches.logo') }}</label>
        @if($branch?->logo_path)
        <div style="margin-bottom:8px;">
            <img src="{{ route('branches.logo', $branch) }}" class="photo-preview" alt="Current logo">
            <div style="font-size:.75rem;color:var(--muted);margin-top:4px;">{{ __('branches.current_logo') }}</div>
        </div>
        @endif
        <input type="file" id="logo" name="logo" class="form-control {{ $errors->has('logo') ? 'is-invalid' : '' }}"
               accept="image/*" onchange="previewLogo(this)">
        @error('logo')<span class="invalid-feedback">{{ $message }}</span>@enderror
        <div id="logoPreviewWrap" style="margin-top:10px;display:none;">
            <img id="logoPreview" class="photo-preview" src="" alt="New logo">
        </div>
    </div>
</div>

<div class="form-group">
    <label class="form-label" for="address">{{ __('branches.address') }}</label>
    <textarea id="address" name="address" class="form-control" rows="2">{{ old('address', $branch?->address) }}</textarea>
</div>

@push('scripts')
<script>
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('logoPreview').src = e.target.result;
            document.getElementById('logoPreviewWrap').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
