<x-app-layout>
    <x-slot name="title">{{ $student->name_km ?: $student->name_en }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $student->name_km ?: $student->name_en }}</h1>
            @if($student->name_km)
            <p class="kia-page-sub">{{ $student->name_en }} &middot; {{ $student->student_code }}</p>
            @else
            <p class="kia-page-sub">{{ $student->student_code }}</p>
            @endif
        </div>
        <a href="{{ route('parent.children') }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    {{-- Attendance summary --}}
    <div class="kia-stats" style="margin-bottom:20px;">
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('Attendance') }}</div>
            <div class="kia-stat-value" style="color:{{ ($attendancePct ?? 0) >= 75 ? 'var(--ok)' : 'var(--warn)' }};">
                {{ $attendancePct !== null ? $attendancePct . '%' : '—' }}
            </div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('Total Records') }}</div>
            <div class="kia-stat-value">{{ $records->total() }}</div>
        </div>
    </div>

    {{-- Attendance history --}}
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('Attendance History') }}</h2></div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('gate.col_arrival') }}</th>
                        <th>{{ __('gate.col_departure') }}</th>
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
                        <td>
                            @if($rec->arrival_time)
                                {{ \Illuminate\Support\Carbon::parse($rec->arrival_time)->format('g:i A') }}
                                @if($rec->method === 'gate_scan')<span class="pill pill-muted" style="font-size:.65rem;">{{ __('gate.via_gate') }}</span>@endif
                            @else — @endif
                        </td>
                        <td>{{ $rec->departure_time ? \Illuminate\Support\Carbon::parse($rec->departure_time)->format('g:i A') : '—' }}</td>
                        <td>{{ $rec->remark ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center">{{ __('No attendance records.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem;">{{ $records->links() }}</div>
    </div>

    @include('documents._list', ['documents' => $documents])
    @include('students._uploaded_documents', ['student' => $student])

    {{-- Published exams --}}
    @if($publishedExams->isNotEmpty())
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('Exam Results') }}</h2></div>
        <div class="kia-card-body">
            @foreach($publishedExams as $exam)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--line);">
                <span style="font-weight:600;">{{ $exam->name }}</span>
                <a href="{{ route('report-card.show', [$exam, $student]) }}" class="btn btn-sm btn-outline">{{ __('Report Card') }}</a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Invoices --}}
    @if($invoices->isNotEmpty())
    <div class="kia-card">
        <div class="kia-card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <h2 class="kia-card-title">{{ __('Invoices') }}</h2>
            <a href="{{ route('billing-statement.show', $student) }}" class="btn btn-sm btn-outline">{{ __('documents.billing_statement') }}</a>
        </div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('Invoice') }}</th>
                        <th>{{ __('Term') }}</th>
                        <th>{{ __('Total') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $inv)
                    <tr>
                        <td><span class="mono">{{ $inv->number }}</span></td>
                        <td>{{ $inv->term }}</td>
                        <td>${{ $inv->total }}</td>
                        <td><span class="pill {{ $inv->status === 'paid' ? 'pill-ok' : 'pill-warn' }}">{{ $inv->status }}</span></td>
                        <td><a href="{{ route('invoices.show', $inv) }}" class="btn btn-xs btn-ghost">{{ __('View') }}</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</x-app-layout>
