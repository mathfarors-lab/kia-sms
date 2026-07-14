<x-guest-layout>
    <p style="color:var(--muted);font-size:.9rem;margin-bottom:20px;">
        {{ __('two_factor.challenge_hint') }}
    </p>

    @if(session('status'))
        <div class="alert alert-info mb-3">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('two-factor.challenge.verify') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="code">{{ __('two_factor.code_label') }} <span class="req">*</span></label>
            <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                   class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}"
                   value="" required autofocus placeholder="123456" style="letter-spacing:.3em;text-align:center;font-size:1.3rem;">
            @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
            <div style="font-size:.78rem;color:var(--muted);margin-top:6px;">{{ __('two_factor.recovery_hint') }}</div>
        </div>

        <button type="submit" class="btn btn-primary w-100" style="justify-content:center;padding:11px;">
            {{ __('two_factor.verify_button') }}
        </button>
    </form>
</x-guest-layout>
