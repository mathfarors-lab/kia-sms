<div class="kia-card" style="margin-top:20px;">
    <div class="kia-card-header"><h2 class="kia-card-title">{{ __('staff_qualifications.section_title') }}</h2></div>
    <div class="kia-card-body">
        @can('staff.edit')
        <form method="POST" action="{{ route('staff-qualifications.store', $staff) }}"
              style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--line);">
            @csrf
            <div class="form-group" style="flex:1;min-width:160px;margin-bottom:0;">
                <label class="form-label" for="qual-degree">{{ __('staff_qualifications.degree') }}</label>
                <input type="text" id="qual-degree" name="degree" class="form-control {{ $errors->has('degree') ? 'is-invalid' : '' }}" maxlength="150" required>
                @error('degree')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" style="flex:1;min-width:160px;margin-bottom:0;">
                <label class="form-label" for="qual-institution">{{ __('staff_qualifications.institution') }}</label>
                <input type="text" id="qual-institution" name="institution" class="form-control {{ $errors->has('institution') ? 'is-invalid' : '' }}" maxlength="150" required>
                @error('institution')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" style="width:110px;margin-bottom:0;">
                <label class="form-label" for="qual-year">{{ __('staff_qualifications.year') }}</label>
                <input type="number" id="qual-year" name="year" class="form-control {{ $errors->has('year') ? 'is-invalid' : '' }}" min="1950" max="{{ date('Y') + 1 }}" value="{{ date('Y') }}" required>
                @error('year')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn btn-primary">{{ __('staff_qualifications.add') }}</button>
        </form>
        @endcan

        @forelse($staff->qualifications as $qualification)
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid var(--line);flex-wrap:wrap;">
            <div>
                <div style="font-weight:600;font-size:.875rem;">{{ $qualification->degree }}</div>
                <div style="font-size:.78rem;color:var(--muted);">{{ $qualification->institution }} &middot; {{ $qualification->year }}</div>
            </div>
            @can('staff.edit')
            <form method="POST" action="{{ route('staff-qualifications.destroy', $qualification) }}" onsubmit="return confirm('{{ __('staff_qualifications.confirm_delete') }}')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
            </form>
            @endcan
        </div>
        @empty
        <p style="color:var(--muted);font-size:.875rem;">{{ __('staff_qualifications.none_yet') }}</p>
        @endforelse
    </div>
</div>
