<x-app-layout>
    <x-slot name="title">{{ __('visitors.title') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('visitors.title') }}</h1>
            <p class="kia-page-sub">{{ __('visitors.on_site_now', ['count' => $currentlyOnSite]) }}</p>
        </div>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="kia-alert" style="background:var(--danger-light,#fee2e2);color:var(--danger,#991b1b)">{{ $errors->first() }}</div>@endif

    <div class="kia-card" style="margin-bottom:16px;max-width:640px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('visitors.log_new') }}</h2></div>
        <div class="kia-card-body">
            <form method="POST" action="{{ route('visitors.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="visitor_name">{{ __('visitors.visitor_name') }} <span class="req">*</span></label>
                        <input type="text" id="visitor_name" name="visitor_name" class="form-control" value="{{ old('visitor_name') }}" required autofocus>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="purpose">{{ __('visitors.purpose') }} <span class="req">*</span></label>
                        <input type="text" id="purpose" name="purpose" class="form-control" value="{{ old('purpose') }}" required placeholder="{{ __('visitors.purpose_placeholder') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="host_staff_id">{{ __('visitors.seeing') }}</label>
                        <select id="host_staff_id" name="host_staff_id" class="form-control">
                            <option value="">—</option>
                            @foreach($staff as $s)
                            <option value="{{ $s->id }}" @selected(old('host_staff_id') == $s->id)>{{ $s->user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('visitors.check_in') }}</button>
            </form>
        </div>
    </div>

    <div class="kia-card">
        <div class="kia-card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <h2 class="kia-card-title">{{ __('visitors.log_title') }}</h2>
            <form method="GET" style="display:flex;gap:8px;">
                <input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()">
            </form>
        </div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr>
                    <th>{{ __('visitors.visitor_name') }}</th><th>{{ __('visitors.purpose') }}</th><th>{{ __('visitors.seeing') }}</th>
                    <th>{{ __('gate.col_arrival') }}</th><th>{{ __('gate.col_departure') }}</th><th></th>
                </tr></thead>
                <tbody>
                @forelse($visitors as $visitor)
                    <tr>
                        <td>{{ $visitor->visitor_name }}</td>
                        <td>{{ $visitor->purpose }}</td>
                        <td>{{ $visitor->hostStaff?->user?->name ?? '—' }}</td>
                        <td>{{ $visitor->time_in->format('g:i A') }}</td>
                        <td>
                            @if($visitor->isCheckedOut())
                                {{ $visitor->time_out->format('g:i A') }}
                            @else
                                <span class="pill pill-warn">{{ __('visitors.still_on_site') }}</span>
                            @endif
                        </td>
                        <td>
                            @unless($visitor->isCheckedOut())
                            <form method="POST" action="{{ route('visitors.check-out', $visitor) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline">{{ __('visitors.check_out') }}</button>
                            </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted);">{{ __('visitors.none_today') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $visitors->links() }}</div>
    </div>
</x-app-layout>
