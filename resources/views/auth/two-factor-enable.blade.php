<x-app-layout>
    <x-slot name="title">{{ __('two_factor.enable_title') }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('two-factor.settings') }}">{{ __('two_factor.settings_title') }}</a>
        <span class="sep">/</span>
        <span>{{ __('two_factor.enable_title') }}</span>
    </div>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('two_factor.enable_title') }}</h1>
    </div>

    @if($errors->any())<div class="kia-alert" style="background:var(--danger-light,#fee2e2);color:var(--danger,#991b1b)">{{ $errors->first() }}</div>@endif

    <div class="kia-card" style="max-width:480px;">
        <div class="kia-card-body" style="text-align:center;">
            <p style="color:var(--muted);font-size:.88rem;margin-bottom:16px;text-align:left;">{{ __('two_factor.scan_hint') }}</p>

            <img src="{{ $qrCode }}" alt="QR code" style="width:200px;height:200px;margin:0 auto 16px;">

            <div style="font-size:.78rem;color:var(--muted);margin-bottom:4px;">{{ __('two_factor.manual_entry_hint') }}</div>
            <div class="mono" style="background:var(--bg-alt);padding:8px 12px;border-radius:6px;font-size:.85rem;letter-spacing:.05em;margin-bottom:24px;word-break:break-all;">{{ $secret }}</div>

            <form method="POST" action="{{ route('two-factor.confirm') }}" style="text-align:left;">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="code">{{ __('two_factor.code_label') }} <span class="req">*</span></label>
                    <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                           class="form-control" required autofocus placeholder="123456"
                           style="letter-spacing:.3em;text-align:center;font-size:1.3rem;">
                </div>
                <button type="submit" class="btn btn-primary w-100" style="justify-content:center;padding:11px;">{{ __('two_factor.confirm_button') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
