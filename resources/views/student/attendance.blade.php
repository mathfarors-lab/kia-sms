<x-app-layout>
    <x-slot name="title">{{ __('My Attendance') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('My Attendance') }}</h1>
            <p class="kia-page-sub">{{ $student->name_km ?: $student->name_en }}</p>
        </div>
    </div>

    {{-- Summary stats --}}
    <div class="kia-stats" style="margin-bottom:24px;">
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('Overall') }}</div>
            <div class="kia-stat-value" style="color:{{ ($attendancePct ?? 0) >= 75 ? 'var(--ok)' : 'var(--warn)' }};">
                {{ $attendancePct !== null ? $attendancePct . '%' : '—' }}
            </div>
            <span class="pill {{ ($attendancePct ?? 0) >= 75 ? 'pill-ok' : 'pill-warn' }}">
                {{ ($attendancePct ?? 0) >= 75 ? __('On Track') : __('Below 75%') }}
            </span>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('Total Days') }}</div>
            <div class="kia-stat-value">{{ $records->total() }}</div>
        </div>
    </div>

    {{-- Quick links --}}
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
        @foreach($publishedExams as $exam)
        <a href="{{ route('report-card.show', [$exam, $student]) }}" class="btn btn-outline btn-sm">
            {{ __('Report Card') }}: {{ $exam->name }}
        </a>
        @endforeach
    </div>

    {{-- Monthly breakdown --}}
    @if($monthly->isNotEmpty())
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('Monthly Breakdown') }}</h2></div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('Month') }}</th>
                        <th>{{ __('School Days') }}</th>
                        <th>{{ __('Present') }}</th>
                        <th>{{ __('Rate') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthly as $row)
                    @php $rate = $row['total'] > 0 ? round($row['present_count'] / $row['total'] * 100) : 0; @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $row['month'])->format('F Y') }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['present_count'] }}</td>
                        <td>
                            <span class="pill {{ $rate >= 75 ? 'pill-ok' : 'pill-warn' }}">{{ $rate }}%</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Full history --}}
    <div class="kia-card">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('Full History') }}</h2></div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Remark') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                    <tr>
                        <td>{{ $rec->date->format('d M Y') }}</td>
                        <td>
                            @php $colors = ['present'=>'pill-ok','late'=>'pill-warn','absent'=>'pill-danger','excused'=>'pill-muted']; @endphp
                            <span class="pill {{ $colors[$rec->status] ?? 'pill-muted' }}">{{ ucfirst($rec->status) }}</span>
                        </td>
                        <td>{{ $rec->remark ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center">{{ __('No attendance records yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem;">{{ $records->links() }}</div>
    </div>
</x-app-layout>
