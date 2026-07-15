<x-guest-layout>
    @if(session('status'))
        <div class="alert alert-info mb-3">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="email">{{ __('Email') }} <span class="req">*</span></label>
            <input id="email" type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                   value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="admin@edu.kh">
            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="password">{{ __('Password') }} <span class="req">*</span></label>
            <input id="password" type="password" name="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                   required autocomplete="current-password" placeholder="••••••••">
            @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <label style="display:flex;align-items:center;gap:8px;font-size:.875rem;cursor:pointer;">
                <input type="checkbox" name="remember" id="remember_me" style="accent-color:var(--royal);">
                {{ __('Remember me') }}
            </label>
            @if(Route::has('password.request'))
            <a href="{{ route('password.request') }}" style="font-size:.8rem;color:var(--muted);">
                {{ __('Forgot password?') }}
            </a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary w-100" style="justify-content:center;padding:11px;">
            {{ __('Sign In') }}
        </button>
    </form>
</x-guest-layout>
