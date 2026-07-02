<x-app-layout>
    <x-slot name="title">Bakong Review</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Bakong Review</h1>
            <p class="kia-page-sub">
                Failed verifications are recorded here for audit and replay.
                They do NOT occupy the idempotency slot — a real payment for the same
                reference can still succeed. Flagged callbacks were verified but
                could not be applied automatically; admin must reconcile them.
            </p>
        </div>
        <a href="{{ route('analytics.index') }}" class="btn btn-ghost">← Analytics</a>
    </div>

    @if(session('success'))
        <div class="kia-alert" style="background:#d1fae5;color:#065f46;border-left:4px solid #059669;margin-bottom:1rem">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="kia-alert" style="background:#fef3c7;color:#92400e;border-left:4px solid #f59e0b;margin-bottom:1rem">
            {{ session('warning') }}
        </div>
    @endif
    @if($errors->any())
        <div class="kia-alert" style="background:#fee2e2;color:#991b1b;border-left:4px solid #dc2626;margin-bottom:1rem">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Summary counts --}}
    <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem">
        <div class="kia-card" style="padding:1rem 1.5rem;min-width:160px">
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase">Sig failures 24 h</div>
            <div style="font-size:2rem;font-weight:700;color:{{ $count24h > 0 ? 'var(--danger)' : 'inherit' }}">
                {{ $count24h }}
            </div>
        </div>
        <div class="kia-card" style="padding:1rem 1.5rem;min-width:160px">
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase">Sig failures 7 days</div>
            <div style="font-size:2rem;font-weight:700">{{ $count7d }}</div>
        </div>
        <div class="kia-card" style="padding:1rem 1.5rem;min-width:160px;border:2px solid {{ $flaggedCount > 0 ? 'var(--warning,#f59e0b)' : 'var(--border)' }}">
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase">Flagged callbacks</div>
            <div style="font-size:2rem;font-weight:700;color:{{ $flaggedCount > 0 ? 'var(--warning,#d97706)' : 'inherit' }}">
                {{ $flaggedCount }}
            </div>
        </div>
    </div>

    @if($count24h > 0)
        <div class="kia-alert" style="background:var(--danger-light,#fee2e2);color:var(--danger,#991b1b);border-left:4px solid var(--danger,#991b1b);margin-bottom:1rem">
            <strong>Signature failures detected in the last 24 h.</strong>
            Check whether <code>BAKONG_WEBHOOK_SECRET</code> is set correctly and matches
            the value in your Bakong merchant portal. If <code>reason</code> is
            <em>secret-unset</em>, the env variable is missing entirely.
            If <em>bad-sig</em>, the secret or signing algorithm may be wrong —
            confirm both against the provider's merchant documentation.<br>
            <strong>To apply a misconfigured-window callback:</strong> fix the env, deploy,
            then click Replay on the affected row below.
        </div>
    @endif

    {{-- ── Failed verifications ──────────────────────────────────────────── --}}
    <div class="kia-card" style="margin-bottom:2rem">
        <div class="kia-card-header">
            <h3 class="kia-card-title">Failed Signature Verifications</h3>
        </div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Transaction Reference</th>
                        <th>Reason</th>
                        <th>Replayed</th>
                        <th>Payload (truncated)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($recent as $row)
                    <tr>
                        <td style="white-space:nowrap">
                            {{ $row->created_at->format('d M Y H:i') }}
                            <div style="font-size:.7rem;color:var(--text-muted)">{{ $row->created_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            <code style="font-size:.8rem">{{ $row->transaction_reference ?? '(missing)' }}</code>
                        </td>
                        <td>
                            @php
                                $reasonColour = match($row->reason) {
                                    'secret-unset'   => 'background:#fee2e2;color:#991b1b',
                                    'bad-sig'        => 'background:#fef3c7;color:#92400e',
                                    'missing-header' => 'background:#e0f2fe;color:#0369a1',
                                    default          => '',
                                };
                            @endphp
                            <span class="kia-badge" style="{{ $reasonColour }}">{{ $row->reason }}</span>
                        </td>
                        <td style="font-size:.8rem">
                            @if($row->replayed_at)
                                <span class="kia-badge"
                                    style="{{ str_starts_with($row->replay_result ?? '', 'still-invalid') ? 'background:#fee2e2;color:#991b1b' : 'background:#d1fae5;color:#065f46' }}">
                                    {{ $row->replay_result }}
                                </span>
                                <div style="font-size:.7rem;color:var(--text-muted)">{{ $row->replayed_at->diffForHumans() }}</div>
                            @else
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                        </td>
                        <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.75rem;font-family:monospace">
                            {{ substr(json_encode($row->raw_payload), 0, 100) }}…
                        </td>
                        <td>
                            @if(!$row->replayed_at || str_starts_with($row->replay_result ?? '', 'still-invalid'))
                                <form method="POST"
                                      action="{{ route('admin.bakong.replay', $row) }}"
                                      onsubmit="return confirm('Re-run HMAC against current config and apply if valid?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-ghost"
                                            style="font-size:.75rem;padding:.25rem .6rem">
                                        Replay
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted)">
                            No failed verifications. ✓
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $recent->links() }}</div>
    </div>

    {{-- ── Flagged verified callbacks ─────────────────────────────────────── --}}
    <div id="flagged" class="kia-card">
        <div class="kia-card-header">
            <h3 class="kia-card-title">Flagged Callbacks (Verified — Require Manual Reconciliation)</h3>
        </div>
        @if($flaggedCount > 0)
            <div style="padding:.75rem 1.25rem;background:#fef3c7;color:#92400e;font-size:.85rem">
                These callbacks passed HMAC verification but could not be automatically applied.
                Payment was <strong>NOT</strong> recorded. Reasons:
                <strong>unmatched-ref</strong> (invoice not found or already paid),
                <strong>amount-mismatch</strong> (callback amount exceeds outstanding balance),
                <strong>currency-mismatch</strong> (non-USD currency).
                Manually reconcile each row in the finance module.
            </div>
        @endif
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Transaction Reference</th>
                        <th>Flag Reason</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Payer</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($flagged as $cb)
                    <tr>
                        <td style="white-space:nowrap">
                            {{ $cb->created_at->format('d M Y H:i') }}
                            <div style="font-size:.7rem;color:var(--text-muted)">{{ $cb->created_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            <code style="font-size:.8rem">{{ $cb->transaction_reference }}</code>
                        </td>
                        <td>
                            @php
                                $flagColour = match($cb->flag_reason) {
                                    'unmatched-ref'    => 'background:#e0f2fe;color:#0369a1',
                                    'amount-mismatch'  => 'background:#fef3c7;color:#92400e',
                                    'currency-mismatch'=> 'background:#fee2e2;color:#991b1b',
                                    default            => '',
                                };
                            @endphp
                            <span class="kia-badge" style="{{ $flagColour }}">{{ $cb->flag_reason }}</span>
                        </td>
                        <td>${{ $cb->amount }}</td>
                        <td>{{ $cb->currency }}</td>
                        <td style="font-size:.8rem">{{ $cb->payer_account ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted)">
                            No flagged callbacks. ✓
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $flagged->links() }}</div>
    </div>
</x-app-layout>
