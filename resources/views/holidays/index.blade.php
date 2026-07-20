<x-app-layout>
    <x-slot name="title">{{ __('academic_calendar.holidays_index_title') }}</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">{{ __('academic_calendar.holidays_index_title') }}</h1></div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('holidays.create') }}" class="btn btn-primary">{{ __('academic_calendar.add_holiday') }}</a>
            <a href="{{ route('academic-calendar.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
        </div>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('academic_calendar.holiday_name') }}</th>
                        <th>{{ __('academic_calendar.start_date') }}</th>
                        <th>{{ __('academic_calendar.end_date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($holidays as $holiday)
                    <tr>
                        <td>{{ $holiday->name }}</td>
                        <td>{{ $holiday->start_date->format('d M Y') }}</td>
                        <td>{{ $holiday->end_date->format('d M Y') }}</td>
                        <td style="text-align:right;display:flex;gap:.4rem;justify-content:flex-end;">
                            <a href="{{ route('holidays.edit', $holiday) }}" class="btn btn-ghost btn-sm">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('holidays.destroy', $holiday) }}" onsubmit="return confirm('{{ __('academic_calendar.confirm_delete_holiday') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="kia-table-empty">{{ __('academic_calendar.no_holidays_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $holidays->links() }}
</x-app-layout>
