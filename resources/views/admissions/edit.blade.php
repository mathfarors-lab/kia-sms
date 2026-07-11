<x-app-layout>
    <x-slot name="title">{{ __('admissions.edit_application') }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('admissions.index') }}">{{ __('admissions.title') }}</a>
        <span class="sep">/</span>
        <a href="{{ route('admissions.show', $application) }}">{{ $application->application_no }}</a>
        <span class="sep">/</span>
        <span>{{ __('Edit') }}</span>
    </div>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('admissions.edit_application') }}</h1>
    </div>

    <div class="kia-card" style="max-width:760px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('admissions.update', $application) }}" enctype="multipart/form-data">
                @csrf @method('PATCH')
                @include('admissions._form', ['application' => $application])
                <div style="display:flex;gap:12px;padding-top:8px;">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    <a href="{{ route('admissions.show', $application) }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
