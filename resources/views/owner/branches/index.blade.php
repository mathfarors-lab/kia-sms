<x-app-layout>
    <x-slot name="title">{{ __('branches.title') }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('branches.title') }}</h1>
        <a href="{{ route('owner.branches.create') }}" class="btn btn-primary">+ {{ __('branches.new_branch') }}</a>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr>
                    <th>{{ __('branches.title') }}</th>
                    <th>{{ __('branches.code') }}</th>
                    <th>{{ __('branches.status') }}</th>
                    <th>{{ __('branches.students_count') }}</th>
                    <th>{{ __('branches.staff_count') }}</th>
                    <th></th>
                </tr></thead>
                <tbody>
                @foreach($branches as $branch)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                @if($branch->logo_path)
                                <img src="{{ route('branches.logo', $branch) }}" alt="" style="width:32px;height:32px;border-radius:6px;object-fit:cover;">
                                @endif
                                <div>
                                    <div style="font-weight:600;">{{ $branch->name_km ?: $branch->name_en }}</div>
                                    <div style="font-size:.78rem;color:var(--muted);">{{ $branch->name_en }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="mono">{{ $branch->code }}</td>
                        <td>
                            <span class="pill {{ $branch->is_active ? 'pill-ok' : 'pill-bad' }}">
                                {{ $branch->is_active ? __('branches.status_active') : __('branches.status_suspended') }}
                            </span>
                        </td>
                        <td>{{ number_format($branch->students_count) }}</td>
                        <td>{{ number_format($branch->staff_count) }}</td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('owner.branches.admins', $branch) }}" class="btn btn-sm btn-ghost">{{ __('branches.manage_admins') }}</a>
                            <a href="{{ route('owner.branches.edit', $branch) }}" class="btn btn-sm btn-outline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('owner.branches.toggle-active', $branch) }}" style="display:inline;"
                                  onsubmit="return confirm('{{ $branch->is_active ? __('branches.suspend_confirm') : '' }}')">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $branch->is_active ? 'btn-ghost' : 'btn-outline' }}"
                                        style="{{ $branch->is_active ? 'color:var(--danger, #c0392b);' : '' }}">
                                    {{ $branch->is_active ? __('branches.suspend') : __('branches.reactivate') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
