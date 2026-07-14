{{-- "Arrivals Today" — polls gate.arrivals-feed every 20s, no websockets needed. --}}
<div class="kia-card" style="margin-bottom:24px;">
    <div class="kia-card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h2 class="kia-card-title">{{ __('gate.arrivals_today') }}</h2>
        @can('gate.scan')
        <a href="{{ route('gate.station') }}" class="btn btn-sm btn-outline" target="_blank">{{ __('gate.view_gate_station') }}</a>
        @endcan
    </div>
    <div class="kia-card-body">
        <div style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
            <span class="pill pill-ok" id="arrivalsPresent">{{ __('gate.present_count') }}: —</span>
            <span class="pill pill-warn" id="arrivalsLate">{{ __('gate.late_count') }}: —</span>
            <span class="pill pill-bad" id="arrivalsAbsent">{{ __('gate.absent_count') }}: —</span>
        </div>
        <div id="arrivalsFeed">
            <div class="kia-empty"><h3>{{ __('gate.no_arrivals_yet') }}</h3></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const feedUrl = @json(route('gate.arrivals-feed'));
    const presentEl = document.getElementById('arrivalsPresent');
    const lateEl = document.getElementById('arrivalsLate');
    const absentEl = document.getElementById('arrivalsAbsent');
    const feedEl = document.getElementById('arrivalsFeed');

    const presentLabel = @json(__('gate.present_count'));
    const lateLabel = @json(__('gate.late_count'));
    const absentLabel = @json(__('gate.absent_count'));
    const emptyHtml = feedEl.innerHTML;

    function render(data) {
        presentEl.textContent = presentLabel + ': ' + data.present;
        lateEl.textContent = lateLabel + ': ' + data.late;
        absentEl.textContent = absentLabel + ': ' + data.absent;

        if (!data.recent || !data.recent.length) {
            feedEl.innerHTML = emptyHtml;
            return;
        }

        feedEl.innerHTML = data.recent.map(function (row) {
            const name = row.name_km || row.name_en;
            const pillClass = row.status === 'late' ? 'pill-warn' : 'pill-ok';
            return '<div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid var(--line);">' +
                '<div><strong>' + name + '</strong> <span style="color:var(--muted);font-size:.8rem;">' + row.student_code + '</span></div>' +
                '<span class="pill ' + pillClass + '">' + (row.arrival_time || '') + '</span>' +
                '</div>';
        }).join('');
    }

    function poll() {
        fetch(feedUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(render)
            .catch(function () { /* transient poll failure — try again next tick */ });
    }

    poll();
    setInterval(poll, 20000);
})();
</script>
@endpush
