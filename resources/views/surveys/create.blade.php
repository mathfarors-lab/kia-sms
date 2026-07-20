<x-app-layout>
    <x-slot name="title">{{ __('surveys.create_new') }}</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">{{ __('surveys.create_new') }}</h1></div>
        <a href="{{ route('surveys.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    <div class="kia-card" style="max-width:760px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('surveys.store') }}" id="kia-survey-form">
                @csrf
                @include('surveys._form')
                <div style="margin-top:1.5rem;">
                    <button type="submit" class="btn btn-primary">{{ __('surveys.create_new') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
