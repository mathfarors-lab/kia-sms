<x-app-layout>
    <x-slot name="title">{{ __('two_factor.recovery_codes_title') }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('two_factor.recovery_codes_title') }}</h1>
    </div>

    <div class="kia-alert" style="background:rgba(224,146,47,.12);color:var(--warn);margin-bottom:16px;max-width:480px;">
        {{ __('two_factor.recovery_codes_warning') }}
    </div>

    <div class="kia-card" style="max-width:480px;">
        <div class="kia-card-body">
            <div class="mono" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:.95rem;margin-bottom:20px;">
                @foreach($codes as $code)
                <div style="background:var(--bg-alt);padding:8px 10px;border-radius:6px;text-align:center;">{{ $code }}</div>
                @endforeach
            </div>
            <a href="{{ route('two-factor.settings') }}" class="btn btn-primary">{{ __('two_factor.recovery_codes_done') }}</a>
        </div>
    </div>
</x-app-layout>
