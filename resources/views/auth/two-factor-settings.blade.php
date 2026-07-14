<x-app-layout>
    <x-slot name="title">{{ __('two_factor.settings_title') }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('two_factor.settings_title') }}</h1>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif

    @if(!$user->hasTwoFactorEnabled() && $user->shouldBeStronglyEncouragedToEnable2fa())
    <div class="kia-alert" style="background:rgba(224,146,47,.12);color:var(--warn);margin-bottom:16px;">
        {{ __('two_factor.recommended_banner') }}
    </div>
    @endif

    <div class="kia-card" style="max-width:560px;">
        <div class="kia-card-body">
            @if($user->hasTwoFactorEnabled())
                <p style="display:flex;align-items:center;gap:8px;font-weight:600;color:var(--ok);margin-bottom:16px;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ __('two_factor.status_enabled') }}
                </p>
                <p style="color:var(--muted);font-size:.88rem;margin-bottom:20px;">{{ __('two_factor.disable_hint') }}</p>

                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="password">{{ __('Current Password') }} <span class="req">*</span></label>
                        <input type="password" id="password" name="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" required>
                        @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <button type="submit" class="btn btn-ghost" style="color:var(--danger,#c0392b);">{{ __('two_factor.disable_button') }}</button>
                </form>
            @else
                <p style="color:var(--muted);font-size:.88rem;margin-bottom:20px;">{{ __('two_factor.status_disabled_hint') }}</p>
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">{{ __('two_factor.enable_button') }}</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
