<x-app-layout>
    <x-slot name="title">{{ __('surveys.title') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('surveys.title') }}</h1>
            <p class="kia-page-sub">{{ $surveys->total() }} {{ __('total') }}</p>
        </div>
        @can('surveys.manage')
        <a href="{{ route('surveys.create') }}" class="btn btn-primary">{{ __('surveys.create_new') }}</a>
        @endcan
    </div>

    @if(session('success'))
    <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('surveys.title_en') }}</th>
                        <th>{{ __('surveys.audience') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('surveys.is_anonymous') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($surveys as $survey)
                    @php $colors = ['draft' => 'pill-muted', 'open' => 'pill-ok', 'closed' => 'pill-bad']; @endphp
                    <tr>
                        <td><a href="{{ route('surveys.show', $survey) }}" class="kia-link">{{ $survey->title_en }}</a></td>
                        <td>{{ __('surveys.audience_' . $survey->audience) }}</td>
                        <td><span class="pill {{ $colors[$survey->status] ?? 'pill-muted' }}">{{ __('surveys.status_' . $survey->status) }}</span></td>
                        <td>{{ $survey->is_anonymous ? __('surveys.yes') : __('surveys.no') }}</td>
                        <td style="text-align:right;"><a href="{{ route('surveys.show', $survey) }}" class="btn btn-ghost btn-sm">{{ __('View') }}</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="kia-table-empty">{{ __('surveys.no_surveys') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($surveys->hasPages())
        <div class="kia-pagination">{{ $surveys->links() }}</div>
        @endif
    </div>
</x-app-layout>
