<x-app-layout>
    <x-slot name="title">{{ __('staff_evaluations.new') }} — {{ $staff->user->name }}</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">{{ __('staff_evaluations.new') }} — {{ $staff->user->name }}</h1></div>
        <a href="{{ route('staff-evaluations.index', $staff) }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    <div class="kia-alert" style="background:var(--paper);color:var(--muted);border:1px solid var(--line);">{{ __('staff_evaluations.draft_note') }}</div>

    <div class="kia-card" style="max-width:680px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('staff-evaluations.store', $staff) }}">
                @csrf
                @include('staff-evaluations._form')
                <button type="submit" class="btn btn-primary">{{ __('staff_evaluations.new') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
