<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('branches.suspended_title') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/kia.css', 'resources/js/app.js'])
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg,#f4f5f9);margin:0;">
    <div class="kia-card" style="max-width:440px;margin:1rem;text-align:center;">
        <div class="kia-card-body" style="padding:2.5rem 2rem;">
            <div style="width:56px;height:56px;border-radius:50%;background:rgba(224,146,47,.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                <svg width="28" height="28" fill="none" stroke="var(--warn,#e0922f)" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 9v4"/><path d="M12 17h.01"/>
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                </svg>
            </div>
            <h1 style="font-size:1.15rem;font-weight:700;margin:0 0 .5rem;">{{ __('branches.suspended_title') }}</h1>
            <p style="color:var(--muted);font-size:.9rem;line-height:1.5;margin:0 0 1.5rem;">
                {{ __('branches.suspended_message', ['branch' => $branch->name_en]) }}
            </p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline">{{ __('Log Out') }}</button>
            </form>
        </div>
    </div>
</body>
</html>
