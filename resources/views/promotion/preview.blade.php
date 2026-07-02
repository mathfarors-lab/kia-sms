<x-app-layout>
    <x-slot name="title">{{ __('promotion.step2_heading') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('promotion.step2_heading') }}</h1>
            <p class="kia-page-sub">
                {{ $fromYear->name }} → {{ $toYear->name }}
            </p>
        </div>
        <a href="{{ route('promotion.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    {{-- Summary counts --}}
    @php
        $totalPromote  = count($preview['promote']);
        $totalRetain   = count($preview['retain']);
        $totalGraduate = count($preview['graduate']);
        $totalWithdraw = count($preview['withdraw']);
        $totalSkipped  = count($preview['skipped']);
        $totalErrors   = count($preview['errors']);
    @endphp

    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;">
                <div style="text-align:center;">
                    <div style="font-size:1.6rem;font-weight:700;color:#2563eb;">{{ $totalPromote }}</div>
                    <div class="kia-stat-label">{{ __('promotion.outcome_promote') }}</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:1.6rem;font-weight:700;color:#d97706;">{{ $totalRetain }}</div>
                    <div class="kia-stat-label">{{ __('promotion.outcome_retain') }}</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:1.6rem;font-weight:700;color:#059669;">{{ $totalGraduate }}</div>
                    <div class="kia-stat-label">{{ __('promotion.outcome_graduate') }}</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:1.6rem;font-weight:700;color:#6b7280;">{{ $totalWithdraw }}</div>
                    <div class="kia-stat-label">{{ __('promotion.outcome_withdraw') }}</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:1.6rem;font-weight:700;color:#9ca3af;">{{ $totalSkipped }}</div>
                    <div class="kia-stat-label">{{ __('promotion.outcome_skipped') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Error banner --}}
    @if($totalErrors > 0)
    <div class="kia-card" style="margin-bottom:20px;border-left:4px solid #ef4444;">
        <div class="kia-card-body">
            <strong style="color:#ef4444;">⚠ {{ __('promotion.errors_warning', ['n' => $totalErrors]) }}</strong>
            <ul style="margin:8px 0 0 16px;font-size:.875rem;">
                @foreach($preview['errors'] as $item)
                <li>{{ $item['student']->name_en }} ({{ $item['student']->student_code }}) — {{ $item['reason'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Main execute form --}}
    <form method="POST" action="{{ route('promotion.execute') }}">
        @csrf
        <input type="hidden" name="from_year_id" value="{{ $fromYear->id }}">
        <input type="hidden" name="to_year_id"   value="{{ $toYear->id }}">

        {{-- Student table --}}
        @php
            $allRows = array_merge(
                array_map(fn($i) => array_merge($i, ['_group' => 'promote']),  $preview['promote']),
                array_map(fn($i) => array_merge($i, ['_group' => 'graduate']), $preview['graduate']),
                array_map(fn($i) => array_merge($i, ['_group' => 'retain']),   $preview['retain']),
                array_map(fn($i) => array_merge($i, ['_group' => 'withdraw']), $preview['withdraw'])
            );
        @endphp

        @if(count($allRows) > 0)
        <div class="kia-card" style="margin-bottom:20px;">
            <div class="kia-table-wrap">
                <table class="kia-table">
                    <thead>
                        <tr>
                            <th>{{ __('promotion.col_student') }}</th>
                            <th>{{ __('promotion.col_code') }}</th>
                            <th>{{ __('promotion.col_current_class') }}</th>
                            <th>{{ __('promotion.col_result') }}</th>
                            <th>{{ __('promotion.col_outcome') }}</th>
                            <th>{{ __('promotion.col_override') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allRows as $item)
                        @php
                            $s     = $item['student'];
                            $sec   = $item['fromSection'];
                            $group = $item['_group'];
                            $reason= $item['reason'];
                            $badgeColor = match($group) {
                                'promote'  => '#2563eb',
                                'graduate' => '#059669',
                                'retain'   => '#d97706',
                                'withdraw' => '#6b7280',
                                default    => '#6b7280',
                            };
                        @endphp
                        <tr>
                            <td>{{ $s->name_km ?: $s->name_en }}</td>
                            <td class="mono">{{ $s->student_code }}</td>
                            <td>
                                @if($sec)
                                    {{ $sec->schoolClass->name ?? '—' }} / {{ $sec->name }}
                                @else
                                    —
                                @endif
                                @if($group === 'promote' && isset($item['toSection']))
                                    <span style="color:#2563eb;font-size:.8rem;">
                                        → {{ $item['toSection']->schoolClass->name ?? '?' }} / {{ $item['toSection']->name }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span style="font-size:.8rem;color:var(--text-muted);">
                                    {{ __('promotion.reason_' . str_replace('-', '_', $reason)) }}
                                </span>
                            </td>
                            <td>
                                <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:600;background:{{ $badgeColor }}1a;color:{{ $badgeColor }};">
                                    {{ __('promotion.outcome_' . $group) }}
                                </span>
                            </td>
                            <td>
                                <select name="overrides[{{ $s->id }}]" class="kia-select" style="font-size:.8rem;padding:3px 6px;">
                                    <option value="promote"  @selected($group === 'promote')>{{ __('promotion.outcome_promote') }}</option>
                                    <option value="retain"   @selected($group === 'retain')>{{ __('promotion.outcome_retain') }}</option>
                                    <option value="graduate" @selected($group === 'graduate')>{{ __('promotion.outcome_graduate') }}</option>
                                    <option value="withdraw" @selected($group === 'withdraw')>{{ __('promotion.outcome_withdraw') }}</option>
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="kia-card" style="margin-bottom:20px;">
            <div class="kia-card-body" style="color:var(--text-muted);">
                {{ __('promotion.no_students') }}
            </div>
        </div>
        @endif

        {{-- Skipped (read-only) --}}
        @if($totalSkipped > 0)
        <div class="kia-card" style="margin-bottom:20px;">
            <div class="kia-card-header">
                <h3 class="kia-card-title" style="font-size:.9rem;">
                    {{ __('promotion.outcome_skipped') }} ({{ $totalSkipped }})
                </h3>
            </div>
            <div class="kia-card-body" style="color:var(--text-muted);font-size:.875rem;">
                @foreach($preview['skipped'] as $item)
                    {{ $item['student']->name_en }} ({{ $item['student']->student_code }})@if(!$loop->last), @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- Execute options --}}
        <div class="kia-card" style="margin-bottom:20px;border-left:4px solid #ef4444;">
            <div class="kia-card-body">
                <label style="display:flex;gap:10px;align-items:center;cursor:pointer;margin-bottom:12px;">
                    <input type="checkbox" name="activate_new_year" value="1" style="width:16px;height:16px;">
                    <span>{{ __('promotion.activate_new_year', ['year' => $toYear->name]) }}</span>
                </label>
                <p style="font-size:.8rem;color:var(--text-muted);margin:0 0 16px;">
                    {{ __('promotion.executing_warning') }}
                </p>
                <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Execute promotion from {{ $fromYear->name }} → {{ $toYear->name }}? This cannot be undone.')">
                    {{ __('promotion.execute_btn') }}
                </button>
            </div>
        </div>
    </form>
</x-app-layout>
