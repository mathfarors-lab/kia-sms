<div class="kia-card" style="margin-top:20px;">
    <div class="kia-card-header"><h2 class="kia-card-title">{{ __('staff_development.section_title') }}</h2></div>
    <div class="kia-card-body">
        @can('staff.edit')
        <form method="POST" action="{{ route('staff-development-logs.store', $staff) }}"
              style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--line);">
            @csrf
            <div class="form-group" style="flex:1;min-width:160px;margin-bottom:0;">
                <label class="form-label" for="dev-title">{{ __('staff_development.title') }}</label>
                <input type="text" id="dev-title" name="title" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" maxlength="150" required>
                @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" style="flex:1;min-width:140px;margin-bottom:0;">
                <label class="form-label" for="dev-provider">{{ __('staff_development.provider') }}</label>
                <input type="text" id="dev-provider" name="provider" class="form-control" maxlength="150">
            </div>
            <div class="form-group" style="width:150px;margin-bottom:0;">
                <label class="form-label" for="dev-date">{{ __('staff_development.completed_date') }}</label>
                <input type="date" id="dev-date" name="completed_date" class="form-control {{ $errors->has('completed_date') ? 'is-invalid' : '' }}" value="{{ now()->toDateString() }}" required>
                @error('completed_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" style="width:90px;margin-bottom:0;">
                <label class="form-label" for="dev-hours">{{ __('staff_development.hours') }}</label>
                <input type="number" id="dev-hours" name="hours" class="form-control" min="0" step="0.5">
            </div>
            <button type="submit" class="btn btn-primary">{{ __('staff_development.add') }}</button>
        </form>
        @endcan

        @forelse($staff->developmentLogs as $log)
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid var(--line);flex-wrap:wrap;">
            <div>
                <div style="font-weight:600;font-size:.875rem;">{{ $log->title }}</div>
                <div style="font-size:.78rem;color:var(--muted);">
                    {{ $log->provider ?? '—' }} &middot; {{ $log->completed_date->format('d M Y') }}
                    @if($log->hours) &middot; {{ $log->hours }} {{ __('staff_development.hours') }} @endif
                </div>
            </div>
            @can('staff.edit')
            <form method="POST" action="{{ route('staff-development-logs.destroy', $log) }}" onsubmit="return confirm('{{ __('staff_development.confirm_delete') }}')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
            </form>
            @endcan
        </div>
        @empty
        <p style="color:var(--muted);font-size:.875rem;">{{ __('staff_development.none_yet') }}</p>
        @endforelse
    </div>
</div>
