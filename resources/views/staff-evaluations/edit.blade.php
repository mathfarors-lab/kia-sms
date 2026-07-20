<x-app-layout>
    <x-slot name="title">{{ __('Edit') }} — {{ __('staff_evaluations.title') }}</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">{{ __('Edit') }} — {{ __('staff_evaluations.title') }}</h1></div>
        <a href="{{ route('staff-evaluations.show', $evaluation) }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    <div class="kia-card" style="max-width:680px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('staff-evaluations.update', $evaluation) }}">
                @csrf
                @method('PUT')
                @include('staff-evaluations._form')
                <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
