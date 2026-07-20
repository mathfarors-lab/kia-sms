<x-app-layout>
    <x-slot name="title">{{ __('staff_evaluations.section_title') }} — {{ $staff->user->name }}</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">{{ __('staff_evaluations.section_title') }} — {{ $staff->user->name }}</h1></div>
        <div style="display:flex;gap:.5rem">
            @can('staff-evaluations.manage')
            <a href="{{ route('staff-evaluations.create', $staff) }}" class="btn btn-primary">{{ __('staff_evaluations.new') }}</a>
            @endcan
            <a href="{{ route('staff.show', $staff) }}" class="btn btn-ghost">{{ __('Back') }}</a>
        </div>
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('staff_evaluations.evaluation_date') }}</th>
                        <th>{{ __('staff_evaluations.overall_rating') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($evaluations as $evaluation)
                    <tr>
                        <td>{{ $evaluation->evaluation_date->format('d M Y') }}</td>
                        <td>{{ $evaluation->overall_rating }} / 5</td>
                        <td><span class="pill {{ $evaluation->status === 'draft' ? 'pill-muted' : 'pill-ok' }}">{{ __('staff_evaluations.status_' . $evaluation->status) }}</span></td>
                        <td style="text-align:right;"><a href="{{ route('staff-evaluations.show', $evaluation) }}" class="btn btn-ghost btn-sm">{{ __('View') }}</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="kia-table-empty">{{ __('staff_evaluations.none_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
