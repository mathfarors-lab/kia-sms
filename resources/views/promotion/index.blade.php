<x-app-layout>
    <x-slot name="title">{{ __('promotion.title') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('promotion.title') }}</h1>
            <p class="kia-page-sub">{{ __('promotion.subtitle') }}</p>
        </div>
    </div>

    {{-- Safety notice --}}
    <div class="kia-card" style="margin-bottom:20px;border-left:4px solid #f59e0b;">
        <div class="kia-card-body" style="display:flex;gap:12px;align-items:flex-start;">
            <span style="font-size:1.4rem;">⚠️</span>
            <div>
                <strong>{{ __('Irreversible bulk operation') }}</strong><br>
                <span style="color:var(--text-muted);font-size:.875rem;">
                    Executing a promotion creates new year enrollments. Prior-year records are never modified.
                    A dry-run preview is shown before anything is written.
                </span>
            </div>
        </div>
    </div>

    <div class="kia-card" style="max-width:560px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('promotion.step1_heading') }}</h2>
        </div>
        <div class="kia-card-body">
            <form method="POST" action="{{ route('promotion.preview') }}">
                @csrf

                @error('from_year_id')
                    <div class="alert alert-danger" style="margin-bottom:12px;">{{ $message }}</div>
                @enderror
                @error('to_year_id')
                    <div class="alert alert-danger" style="margin-bottom:12px;">{{ $message }}</div>
                @enderror

                <div class="form-group">
                    <label class="form-label">{{ __('promotion.from_year') }}</label>
                    <select name="from_year_id" class="form-control" required>
                        @foreach($years as $year)
                            <option value="{{ $year->id }}"
                                @selected(old('from_year_id', $fromYear?->id) == $year->id)>
                                {{ $year->name }}{{ $year->is_active ? ' ✓ (active)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('promotion.to_year') }}</label>
                    <select name="to_year_id" class="form-control" required>
                        <option value="">— {{ __('Select') }} —</option>
                        @foreach($years as $year)
                            <option value="{{ $year->id }}" @selected(old('to_year_id') == $year->id)>
                                {{ $year->name }}{{ $year->is_active ? ' ✓ (active)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <small class="kia-stat-label" style="display:block;margin-top:4px;">
                        {{ __('promotion.to_year_hint') }}
                    </small>
                </div>

                <div class="form-actions" style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary">{{ __('promotion.preview_btn') }}</button>
                    <a href="{{ url()->previous() }}" class="btn btn-ghost">{{ __('Back') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
