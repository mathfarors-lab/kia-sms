<x-app-layout>
    <x-slot name="title">{{ $survey->title_en }}</x-slot>

    @php $colors = ['draft' => 'pill-muted', 'open' => 'pill-ok', 'closed' => 'pill-bad']; @endphp

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $survey->title_km ?: $survey->title_en }}</h1>
            <p class="kia-page-sub">
                <span class="pill {{ $colors[$survey->status] ?? 'pill-muted' }}">{{ __('surveys.status_' . $survey->status) }}</span>
                &middot; {{ __('surveys.audience_' . $survey->audience) }}
                @if($survey->is_anonymous) &middot; {{ __('surveys.is_anonymous') }} @endif
            </p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('surveys.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
            @can('surveys.manage')
                @if($survey->status === 'draft')
                <a href="{{ route('surveys.edit', $survey) }}" class="btn btn-outline">{{ __('Edit') }}</a>
                <form method="POST" action="{{ route('surveys.publish', $survey) }}" onsubmit="return confirm('{{ __('surveys.published') }}?')">
                    @csrf
                    <button type="submit" class="btn btn-primary">{{ __('surveys.status_open') }}</button>
                </form>
                @elseif($survey->status === 'open')
                <form method="POST" action="{{ route('surveys.close', $survey) }}" onsubmit="return confirm('{{ __('surveys.closed') }}?')">
                    @csrf
                    <button type="submit" class="btn btn-outline">{{ __('surveys.status_closed') }}</button>
                </form>
                @endif
                <a href="{{ route('surveys.results', $survey) }}" class="btn btn-outline">{{ __('surveys.results') }}</a>
            @endcan
        </div>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="kia-alert kia-alert-danger">{{ $errors->first() }}</div>@endif

    @if($survey->description_en)
    <div class="kia-card" style="margin-bottom:1rem;">
        <div class="kia-card-body">{{ $survey->description_km ?: $survey->description_en }}</div>
    </div>
    @endif

    @can('surveys.manage')
    <div class="kia-stats" style="margin-bottom:1.5rem">
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('surveys.response_rate') }}</div>
            <div class="kia-stat-value">{{ $completedCount }} / {{ $targetedCount }}</div>
        </div>
    </div>
    @endcan

    <div class="kia-card">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('surveys.questions') }}</h2></div>
        <div class="kia-card-body">
            @foreach($survey->questions as $i => $question)
            <div style="padding:10px 0;border-bottom:1px solid var(--line);">
                <strong>Q{{ $i + 1 }}.</strong> {{ $question->question_text_km ?: $question->question_text_en }}
                <span style="color:var(--muted);font-size:.8rem;"> — {{ __('surveys.type_' . $question->type) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
