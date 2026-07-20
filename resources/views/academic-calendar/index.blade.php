<x-app-layout>
    <x-slot name="title">{{ __('academic_calendar.page_title') }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('academic_calendar.page_title') }}</h1>
        <div style="display:flex;gap:.5rem;align-items:center;">
            <a href="{{ route('academic-calendar.index', ['month' => $prevMonth]) }}" class="btn btn-ghost btn-sm">&larr;</a>
            <span style="font-weight:600;min-width:140px;text-align:center;">{{ $month->format('F Y') }}</span>
            <a href="{{ route('academic-calendar.index', ['month' => $nextMonth]) }}" class="btn btn-ghost btn-sm">&rarr;</a>
            <a href="{{ route('academic-calendar.index') }}" class="btn btn-ghost btn-sm">{{ __('academic_calendar.today') }}</a>
            @can('academic-calendar.manage')
            <a href="{{ route('holidays.index') }}" class="btn btn-outline">{{ __('academic_calendar.manage_holidays') }}</a>
            @endcan
        </div>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif

    <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;font-size:.78rem;color:var(--muted);">
        <span><span class="kia-cal-dot kia-cal-dot--holiday"></span> {{ __('academic_calendar.legend_holiday') }}</span>
        <span><span class="kia-cal-dot kia-cal-dot--exam"></span> {{ __('academic_calendar.legend_exam') }}</span>
        <span><span class="kia-cal-dot kia-cal-dot--semester"></span> {{ __('academic_calendar.legend_semester') }}</span>
        <span><span class="kia-cal-dot kia-cal-dot--year"></span> {{ __('academic_calendar.legend_year') }}</span>
    </div>

    <style>
        .kia-cal-dot { display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px; }
        .kia-cal-dot--holiday { background:#e0575b; }
        .kia-cal-dot--exam { background:#4d7cfe; }
        .kia-cal-dot--semester { background:#f2a93b; }
        .kia-cal-dot--year { background:#8b5cf6; }
        .kia-cal-grid { display:grid;grid-template-columns:repeat(7,1fr);gap:1px;background:var(--line);border:1px solid var(--line);border-radius:8px;overflow:hidden; }
        .kia-cal-head { background:var(--paper);padding:8px;text-align:center;font-size:.75rem;font-weight:600;color:var(--muted); }
        .kia-cal-cell { background:var(--card,#fff);min-height:92px;padding:6px;font-size:.75rem;vertical-align:top; }
        .kia-cal-cell--out { opacity:.4; }
        .kia-cal-cell--today { box-shadow:inset 0 0 0 2px var(--royal); }
        .kia-cal-daynum { font-weight:600;margin-bottom:4px; }
        .kia-cal-event { display:block;margin-bottom:2px;padding:1px 4px;border-radius:4px;font-size:.68rem;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .kia-cal-event--holiday { background:#fbe4e4;color:#a12227; }
        .kia-cal-event--exam { background:#e4eaff;color:#28459c; }
        .kia-cal-event--semester { background:#fdf0d9;color:#8a5a10; }
        .kia-cal-event--year { background:#ece3fd;color:#5b32a8; }
    </style>

    <div class="kia-cal-grid">
        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)
        <div class="kia-cal-head">{{ $d }}</div>
        @endforeach

        @foreach($weeks as $week)
            @foreach($week as $day)
            <div class="kia-cal-cell {{ !$day['inCurrentMonth'] ? 'kia-cal-cell--out' : '' }} {{ $day['isToday'] ? 'kia-cal-cell--today' : '' }}">
                <div class="kia-cal-daynum">{{ $day['date']->day }}</div>
                @foreach($day['events'] as $event)
                <span class="kia-cal-event kia-cal-event--{{ $event['type'] }}" title="{{ $event['label'] }}">{{ $event['label'] }}</span>
                @endforeach
            </div>
            @endforeach
        @endforeach
    </div>

    <div class="kia-card" style="margin-top:1.5rem;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('academic_calendar.events_in', ['month' => $month->format('F Y')]) }}</h2></div>
        <div class="kia-card-body">
            @forelse($monthEvents as $event)
            <div style="display:flex;gap:10px;align-items:center;padding:6px 0;border-bottom:1px solid var(--line);font-size:.85rem;">
                <span class="kia-cal-dot kia-cal-dot--{{ $event['type'] }}"></span>
                <span style="width:90px;color:var(--muted);">{{ \Illuminate\Support\Carbon::parse($event['date'])->format('d M') }}</span>
                <span>{{ $event['label'] }}</span>
            </div>
            @empty
            <p style="color:var(--muted);font-size:.875rem;">{{ __('academic_calendar.no_events_this_month') }}</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
