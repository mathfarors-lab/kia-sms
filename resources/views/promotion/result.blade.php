<x-app-layout>
    <x-slot name="title">{{ __('promotion.step3_heading') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('promotion.step3_heading') }}</h1>
            <p class="kia-page-sub">{{ $fromYear->name }} → {{ $toYear->name }}</p>
        </div>
    </div>

    <div class="kia-card" style="max-width:600px;border-left:4px solid #059669;">
        <div class="kia-card-body">
            <div style="margin-bottom:20px;font-size:1rem;">
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;">
                    <li>
                        <span style="display:inline-block;width:24px;">✅</span>
                        {{ __('promotion.result_promoted',  ['n' => $counts['promoted']]) }}
                    </li>
                    <li>
                        <span style="display:inline-block;width:24px;">🔁</span>
                        {{ __('promotion.result_retained',  ['n' => $counts['retained']]) }}
                    </li>
                    <li>
                        <span style="display:inline-block;width:24px;">🎓</span>
                        {{ __('promotion.result_graduated', ['n' => $counts['graduated']]) }}
                    </li>
                    <li>
                        <span style="display:inline-block;width:24px;">📤</span>
                        {{ __('promotion.result_withdrawn', ['n' => $counts['withdrawn']]) }}
                    </li>
                    <li>
                        <span style="display:inline-block;width:24px;">⏭</span>
                        {{ __('promotion.result_skipped',   ['n' => $counts['skipped']]) }}
                    </li>
                    @if($counts['errors'] > 0)
                    <li style="color:#ef4444;">
                        <span style="display:inline-block;width:24px;">⚠</span>
                        {{ __('promotion.result_errors',    ['n' => $counts['errors']]) }}
                    </li>
                    @endif
                </ul>
            </div>

            @if($toYear->is_active)
            <p style="color:#059669;font-size:.875rem;">
                {{ __('promotion.year_activated', ['year' => $toYear->name]) }}
            </p>
            @endif

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
                <a href="{{ route('promotion.index') }}" class="btn btn-ghost">{{ __('New Rollover') }}</a>
                <a href="{{ route('term-results.index') }}" class="btn btn-outline">{{ __('Term Results') }}</a>
                <a href="{{ route('dashboard') }}" class="btn btn-outline">{{ __('Dashboard') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
