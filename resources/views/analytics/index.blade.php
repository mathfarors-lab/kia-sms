<x-app-layout>
    <x-slot name="title">Analytics</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Analytics</h1></div>
        <form method="GET" action="{{ route('analytics.index') }}" style="display:flex;gap:.5rem;align-items:center">
            <select name="year_id" class="form-control" style="width:auto" onchange="this.form.submit()">
                @foreach($years as $y)
                    <option value="{{ $y->id }}" @selected($y->id === $year->id)>{{ $y->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Stat cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div class="kia-card" style="padding:1.25rem">
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">Enrolled Students</div>
            <div style="font-size:2rem;font-weight:700;margin-top:.25rem">{{ number_format($stats['enrolledCount']) }}</div>
        </div>
        <div class="kia-card" style="padding:1.25rem">
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">Attendance Rate</div>
            <div style="font-size:2rem;font-weight:700;margin-top:.25rem">
                {{ $stats['attendanceRate'] !== null ? $stats['attendanceRate'] . '%' : 'N/A' }}
            </div>
        </div>
        <div class="kia-card" style="padding:1.25rem">
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">Fees Collected</div>
            <div style="font-size:2rem;font-weight:700;margin-top:.25rem">${{ number_format($stats['feeCollection'], 2) }}</div>
        </div>
        <div class="kia-card" style="padding:1.25rem">
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">Outstanding Fees</div>
            <div style="font-size:2rem;font-weight:700;margin-top:.25rem;color:var(--danger)">${{ number_format($stats['feeOutstanding'], 2) }}</div>
        </div>
        <div class="kia-card" style="padding:1.25rem">
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">Pending Leaves</div>
            <div style="font-size:2rem;font-weight:700;margin-top:.25rem">{{ $stats['pendingLeaves'] }}</div>
        </div>
        <div class="kia-card" style="padding:1.25rem">
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">Overdue Books</div>
            <div style="font-size:2rem;font-weight:700;margin-top:.25rem;{{ $stats['overdueBooks'] > 0 ? 'color:var(--danger)' : '' }}">{{ $stats['overdueBooks'] }}</div>
        </div>
        @can(\App\Support\Permissions::SETTINGS_MANAGE)
        <div class="kia-card" style="padding:1.25rem;border:2px solid {{ $stats['failedBakong24h'] > 0 ? 'var(--danger)' : 'var(--border)' }}">
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">
                Bakong Failed Verifications
            </div>
            <div style="font-size:2rem;font-weight:700;margin-top:.25rem;{{ $stats['failedBakong24h'] > 0 ? 'color:var(--danger)' : '' }}">
                {{ $stats['failedBakong24h'] }}
                <span style="font-size:.9rem;font-weight:400;color:var(--text-muted)">24h</span>
            </div>
            <div style="font-size:.8rem;color:var(--text-muted);margin-top:.25rem">{{ $stats['failedBakong7d'] }} this week</div>
            @if($stats['failedBakong24h'] > 0)
                <a href="{{ route('admin.bakong.failed') }}" style="font-size:.75rem;color:var(--danger);margin-top:.5rem;display:block">
                    ⚠ Inspect failures →
                </a>
            @else
                <a href="{{ route('admin.bakong.failed') }}" style="font-size:.75rem;color:var(--text-muted);margin-top:.5rem;display:block">View audit log →</a>
            @endif
        </div>
        @if($stats['flaggedCallbacks'] > 0)
        <div class="kia-card" style="padding:1.25rem;border:2px solid var(--warning,#f59e0b)">
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">
                Bakong Flagged Callbacks
            </div>
            <div style="font-size:2rem;font-weight:700;margin-top:.25rem;color:var(--warning,#d97706)">
                {{ $stats['flaggedCallbacks'] }}
            </div>
            <div style="font-size:.8rem;color:var(--text-muted);margin-top:.25rem">Verified but not applied</div>
            <a href="{{ route('admin.bakong.failed') }}#flagged" style="font-size:.75rem;color:var(--warning,#d97706);margin-top:.5rem;display:block">
                Review flagged →
            </a>
        </div>
        @endif
        @endcan
    </div>

    {{-- Attendance by month --}}
    <div class="kia-card" style="margin-bottom:1rem">
        <div class="kia-card-header"><h3 class="kia-card-title">Attendance Rate by Month</h3></div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr><th>Month</th><th>Total Days</th><th>Present</th><th>Rate</th></tr></thead>
                <tbody>
                @forelse($byMonth as $row)
                    <tr>
                        <td>{{ $row['month'] }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['present'] }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:.5rem">
                                <div style="height:8px;width:80px;background:var(--surface-2);border-radius:4px;overflow:hidden">
                                    <div style="height:100%;width:{{ $row['rate'] }}%;background:var(--primary);border-radius:4px"></div>
                                </div>
                                {{ $row['rate'] }}%
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-muted)">No attendance data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Fee collection by month --}}
    <div class="kia-card">
        <div class="kia-card-header"><h3 class="kia-card-title">Fee Collection by Month</h3></div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr><th>Month</th><th>Collected (USD)</th></tr></thead>
                <tbody>
                @forelse($feeByMonth as $row)
                    <tr>
                        <td>{{ $row->month }}</td>
                        <td>${{ number_format($row->collected, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" style="text-align:center;padding:2rem;color:var(--text-muted)">No payment data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
