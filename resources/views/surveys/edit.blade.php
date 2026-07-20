<x-app-layout>
    <x-slot name="title">{{ __('Edit') }} — {{ $survey->title_en }}</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">{{ __('Edit') }} — {{ $survey->title_en }}</h1></div>
        <a href="{{ route('surveys.show', $survey) }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    <div class="kia-card" style="max-width:760px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('surveys.update', $survey) }}" id="kia-survey-form">
                @csrf
                @method('PUT')
                @include('surveys._form')
                <div style="margin-top:1.5rem;">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
