<x-app-layout>
    <x-slot name="title">{{ __('surveys.my_surveys') }}</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">{{ __('surveys.my_surveys') }}</h1></div>
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr><th>{{ __('surveys.title_en') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($surveys as $survey)
                    <tr>
                        <td>{{ $survey->title_km ?: $survey->title_en }}</td>
                        <td style="text-align:right;"><a href="{{ route('surveys.take', $survey) }}" class="btn btn-primary btn-sm">{{ __('surveys.take_survey') }}</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="kia-table-empty">{{ __('surveys.no_surveys') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
