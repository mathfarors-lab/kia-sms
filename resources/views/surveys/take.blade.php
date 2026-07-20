<x-app-layout>
    <x-slot name="title">{{ $survey->title_en }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $survey->title_km ?: $survey->title_en }}</h1>
            @if($survey->description_en)<p class="kia-page-sub">{{ $survey->description_km ?: $survey->description_en }}</p>@endif
        </div>
    </div>

    @if($errors->any())<div class="kia-alert kia-alert-danger">{{ $errors->first() }}</div>@endif

    @if($survey->is_anonymous)
    <div class="kia-alert" style="background:var(--paper);color:var(--muted);border:1px solid var(--line);">{{ __('surveys.anonymous_notice') }}</div>
    @endif

    <div class="kia-card" style="max-width:680px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('surveys.submit', $survey) }}">
                @csrf

                @foreach($survey->questions as $i => $question)
                <div class="form-group" style="padding-bottom:1rem;margin-bottom:1rem;border-bottom:1px solid var(--line);">
                    <label class="form-label">
                        Q{{ $i + 1 }}. {{ $question->question_text_km ?: $question->question_text_en }}
                        @if($question->required)<span style="color:var(--bad);">*</span>@endif
                    </label>

                    @if($question->type === 'multiple_choice')
                        @foreach($question->options ?? [] as $option)
                        <label style="display:flex;align-items:center;gap:.5rem;font-weight:400;padding:.25rem 0;">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option }}" {{ $question->required ? 'required' : '' }}>
                            {{ $option }}
                        </label>
                        @endforeach
                    @elseif($question->type === 'rating_scale')
                        <div style="display:flex;gap:1rem;">
                            @for($rating = 1; $rating <= 5; $rating++)
                            <label style="display:flex;flex-direction:column;align-items:center;gap:.25rem;font-weight:400;">
                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $rating }}" {{ $question->required ? 'required' : '' }}>
                                {{ $rating }}
                            </label>
                            @endfor
                        </div>
                    @elseif($question->type === 'yes_no')
                        <label style="display:flex;align-items:center;gap:.5rem;font-weight:400;padding:.25rem 0;">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ __('surveys.yes') }}" {{ $question->required ? 'required' : '' }}>
                            {{ __('surveys.yes') }}
                        </label>
                        <label style="display:flex;align-items:center;gap:.5rem;font-weight:400;padding:.25rem 0;">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ __('surveys.no') }}" {{ $question->required ? 'required' : '' }}>
                            {{ __('surveys.no') }}
                        </label>
                    @else
                        <textarea name="answers[{{ $question->id }}]" rows="3" class="form-control" {{ $question->required ? 'required' : '' }}></textarea>
                    @endif
                    @error('answers.' . $question->id)<span class="invalid-feedback" style="display:block;">{{ $message }}</span>@enderror
                </div>
                @endforeach

                <button type="submit" class="btn btn-primary">{{ __('surveys.submit') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
