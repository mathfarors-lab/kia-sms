<x-app-layout>
    <x-slot name="title">{{ __('staff_evaluations.title') }} — {{ $evaluation->staff->user->name }}</x-slot>

    @php $isDraft = $evaluation->status === \App\Models\StaffEvaluation::STATUS_DRAFT; @endphp

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('staff_evaluations.title') }}</h1>
            <p class="kia-page-sub">
                {{ $evaluation->staff->user->name }} &middot; {{ $evaluation->evaluation_date->format('d M Y') }}
                &middot; <span class="pill {{ $isDraft ? 'pill-muted' : 'pill-ok' }}">{{ __('staff_evaluations.status_' . $evaluation->status) }}</span>
            </p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('staff-evaluations.index', $evaluation->staff) }}" class="btn btn-ghost">{{ __('Back') }}</a>
            @can('staff-evaluations.manage')
                @if($isDraft)
                <a href="{{ route('staff-evaluations.edit', $evaluation) }}" class="btn btn-outline">{{ __('Edit') }}</a>
                <form method="POST" action="{{ route('staff-evaluations.finalize', $evaluation) }}" onsubmit="return confirm('{{ __('staff_evaluations.finalize_confirm') }}')">
                    @csrf
                    <button type="submit" class="btn btn-primary">{{ __('staff_evaluations.finalize') }}</button>
                </form>
                @endif
            @endcan
        </div>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif

    @if($isDraft)
    <div class="kia-alert" style="background:var(--paper);color:var(--muted);border:1px solid var(--line);">{{ __('staff_evaluations.draft_note') }}</div>
    @endif

    <div class="kia-card" style="max-width:680px;">
        <div class="kia-card-body">
            <table style="width:100%;font-size:.875rem;border-collapse:collapse;">
                @foreach([
                    ['label' => __('staff_evaluations.overall_rating'), 'value' => $evaluation->overall_rating . ' / 5'],
                    ['label' => __('staff_evaluations.evaluated_by'), 'value' => $evaluation->evaluator->name ?? '—'],
                    ['label' => __('staff_evaluations.strengths'), 'value' => $evaluation->strengths ?: '—'],
                    ['label' => __('staff_evaluations.areas_for_improvement'), 'value' => $evaluation->areas_for_improvement ?: '—'],
                    ['label' => __('staff_evaluations.comments'), 'value' => $evaluation->comments ?: '—'],
                ] as $row)
                <tr>
                    <td style="padding:10px 0;color:var(--muted);width:35%;font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--line);vertical-align:top;">{{ $row['label'] }}</td>
                    <td style="padding:10px 0;border-bottom:1px solid var(--line);white-space:pre-line;">{{ $row['value'] }}</td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
</x-app-layout>
