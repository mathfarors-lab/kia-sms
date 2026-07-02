<x-app-layout>
    <x-slot name="title">{{ __('Audit Log') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('Audit Log') }}</h1>
            <p class="kia-page-sub">{{ __('System activity recorded by spatie/activitylog') }}</p>
        </div>
    </div>

    {{-- Filter form --}}
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-body">
            <form method="GET" action="{{ route('audit.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                <div>
                    <label class="form-label">{{ __('Causer') }}</label>
                    <select name="causer_id" class="form-control" style="min-width:180px;">
                        <option value="">{{ __('— All users —') }}</option>
                        @foreach($causers as $u)
                        <option value="{{ $u->id }}" @selected(request('causer_id') == $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">{{ __('Type') }}</label>
                    <select name="log_name" class="form-control" style="min-width:160px;">
                        <option value="">{{ __('— All types —') }}</option>
                        @foreach($logNames as $ln)
                        <option value="{{ $ln }}" @selected(request('log_name') === $ln)>{{ $ln }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">{{ __('From') }}</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('To') }}</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('audit.index') }}" class="btn btn-ghost">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Results table --}}
    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('When') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Event') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Subject') }}</th>
                        <th>{{ __('Causer') }}</th>
                        <th>{{ __('Changes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($query as $log)
                    @php
                        $attrs = $log->properties['attributes'] ?? [];
                        $old   = $log->properties['old'] ?? [];
                        $safe  = array_diff_key($attrs, array_flip(['password', 'remember_token', 'api_token', 'token', 'secret']));
                        $safeOld = array_diff_key($old, array_flip(['password', 'remember_token', 'api_token', 'token', 'secret']));
                    @endphp
                    <tr>
                        <td style="white-space:nowrap;font-size:.8125rem;color:var(--muted);">
                            {{ $log->created_at->format('d M Y H:i') }}
                        </td>
                        <td><span class="pill pill-royal">{{ $log->log_name }}</span></td>
                        <td>{{ $log->event ?? '—' }}</td>
                        <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $log->description }}
                        </td>
                        <td style="font-size:.8125rem;">
                            @if($log->subject_type)
                            {{ class_basename($log->subject_type) }}
                            @if($log->subject_id) #{{ $log->subject_id }} @endif
                            @else
                            —
                            @endif
                        </td>
                        <td style="font-size:.8125rem;">
                            {{ $log->causer?->name ?? __('System') }}
                        </td>
                        <td style="font-size:.75rem;max-width:260px;">
                            @if(count($safe))
                            @foreach($safe as $field => $val)
                            <div>
                                <span style="color:var(--muted);">{{ $field }}:</span>
                                @if(isset($safeOld[$field]))
                                <span style="color:#ef4444;text-decoration:line-through;">{{ $safeOld[$field] }}</span>
                                →
                                @endif
                                <span>{{ is_array($val) ? json_encode($val) : $val }}</span>
                            </div>
                            @endforeach
                            @elseif($log->properties->isNotEmpty() && empty($log->properties['attributes']))
                            <span style="color:var(--muted);">{{ $log->properties->except(['attributes','old'])->toJson() }}</span>
                            @else
                            —
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">{{ __('No activity found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($query->hasPages())
    <div style="margin-top:16px;">{{ $query->links() }}</div>
    @endif
</x-app-layout>
