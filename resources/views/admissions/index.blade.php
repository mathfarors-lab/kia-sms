<x-app-layout>
    <x-slot name="title">{{ __('admissions.title') }}</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">{{ __('admissions.title') }}</h1></div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('admissions.export-excel', request()->query()) }}" class="btn btn-ghost">↓ Excel</a>
            <a href="{{ route('admissions.export-pdf', request()->query()) }}" class="btn btn-ghost" target="_blank">↓ PDF</a>
            @can('admissions.manage')
            <a href="{{ route('admissions.create') }}" class="btn btn-primary">+ {{ __('admissions.new_application') }}</a>
            @endcan
        </div>
    </div>

    <p style="color:var(--muted);font-size:.875rem;max-width:70ch;margin:-8px 0 16px;">{{ __('admissions.relationship_note') }}</p>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="kia-alert" style="background:var(--danger-light,#fee2e2);color:var(--danger,#991b1b)">{{ session('error') }}</div>@endif

    {{-- Pipeline counters --}}
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
        @foreach(\App\Models\AdmissionApplication::STATUSES as $s)
        <a href="{{ route('admissions.index', ['status' => $s]) }}" class="pill {{ request('status') === $s ? 'pill-royal' : 'pill-muted' }}"
           style="text-decoration:none;">
            {{ __('admissions.status_' . $s) }}: {{ $counts[$s] ?? 0 }}
        </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="kia-card" style="margin-bottom:16px;">
        <div class="kia-card-body">
            <form method="GET" action="{{ route('admissions.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                <div>
                    <label class="form-label">{{ __('admissions.status') }}</label>
                    <select name="status" class="form-control" style="min-width:170px;">
                        <option value="">{{ __('admissions.all_statuses') }}</option>
                        @foreach(\App\Models\AdmissionApplication::STATUSES as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('admissions.status_' . $s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">{{ __('Search') }}</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('admissions.search_hint') }}">
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
            </form>
        </div>
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr>
                    <th>{{ __('admissions.application_no') }}</th>
                    <th>{{ __('admissions.applicant') }}</th>
                    <th>{{ __('admissions.desired_class') }}</th>
                    <th>{{ __('admissions.guardian_phone') }}</th>
                    <th>{{ __('admissions.status') }}</th>
                    <th>{{ __('admissions.submitted') }}</th>
                </tr></thead>
                <tbody>
                @forelse($applications as $app)
                    <tr>
                        <td><a href="{{ route('admissions.show', $app) }}" class="mono" style="color:var(--royal);">{{ $app->application_no }}</a></td>
                        <td>
                            <div style="font-weight:600;">{{ $app->name_km ?: $app->name_en }}</div>
                            <div style="font-size:.78rem;color:var(--muted);">{{ $app->name_en }}</div>
                        </td>
                        <td>{{ $app->desiredClass?->name ?? '—' }}</td>
                        <td>{{ $app->guardian_phone ?? '—' }}</td>
                        <td><x-admission-status-pill :status="$app->status" /></td>
                        <td style="white-space:nowrap;color:var(--muted);font-size:.82rem;">{{ $app->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted)">{{ __('admissions.no_applications') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $applications->links() }}</div>
    </div>
</x-app-layout>
