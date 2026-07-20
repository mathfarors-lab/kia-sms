<x-app-layout>
    <x-slot name="title">{{ __('discipline_records.log_incident') }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('discipline_records.log_incident') }} — {{ $student->name_en }}</h1>
        <a href="{{ route('discipline-incidents.index', $student) }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    <div class="kia-card" style="max-width:560px">
        <form method="POST" action="{{ route('discipline-incidents.store', $student) }}" class="kia-form">
            @csrf
            @include('discipline._form')
            <div class="kia-form-actions">
                <button type="submit" class="btn btn-primary">{{ __('discipline_records.log_incident') }}</button>
                <a href="{{ route('discipline-incidents.index', $student) }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
