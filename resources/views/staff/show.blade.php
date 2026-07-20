<x-app-layout>
    <x-slot name="title">{{ $staff->user->name }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('staff.index') }}">{{ __('Staff') }}</a>
        <span class="sep">/</span>
        <span>{{ $staff->user->name }}</span>
    </div>

    <div class="kia-page-header">
        <div class="d-flex align-center gap-3">
            @if($staff->photo)
            <img src="{{ route('staff.photo', $staff) }}" alt="{{ $staff->user->name }}"
                 style="width:56px;height:56px;border-radius:50%;object-fit:cover;">
            @else
            <div class="kia-avatar" style="width:56px;height:56px;font-size:1.1rem;border-radius:50%;">
                {{ strtoupper(substr($staff->user->name, 0, 2)) }}
            </div>
            @endif
            <div>
                <h1 class="kia-page-title" style="margin-bottom:2px;">{{ $staff->user->name }}</h1>
                <div style="color:var(--muted);font-size:.875rem;">{{ $staff->user->email }}</div>
                <span class="mono" style="font-size:.82rem;color:var(--royal);">{{ $staff->staff_code }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($staff->user->hasRole('teacher'))
            <a href="{{ route('staff.teaching-schedule', $staff) }}" class="btn btn-outline">{{ __('timetable.teaching_schedule') }}</a>
            @endif
            @can('staff-evaluations.manage')
            <a href="{{ route('staff-evaluations.index', $staff) }}" class="btn btn-outline">{{ __('staff_evaluations.section_title') }}</a>
            @endcan
            @can('staff.edit')
            <a href="{{ route('staff.edit', $staff) }}" class="btn btn-outline">{{ __('Edit') }}</a>
            @endcan
        </div>
    </div>

    <div class="kia-card" style="max-width:600px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('Details') }}</h2></div>
        <div class="kia-card-body">
            <table style="width:100%;font-size:.875rem;border-collapse:collapse;">
                @foreach([
                    ['label' => __('Role'),       'value' => implode(', ', $staff->user->getRoleNames()->toArray())],
                    ['label' => __('Position'),   'value' => $staff->position ?? '—'],
                    ['label' => __('Department'), 'value' => $staff->department ?? '—'],
                    ['label' => __('Joined'),     'value' => $staff->joined_at?->format('d M Y') ?? '—'],
                    ['label' => __('Phone'),      'value' => $staff->user->phone ?? '—'],
                    ['label' => __('Salary'),     'value' => $staff->salary ? '$'.number_format($staff->salary, 2) : '—'],
                ] as $row)
                <tr>
                    <td style="padding:10px 0;color:var(--muted);width:40%;font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--line);">{{ $row['label'] }}</td>
                    <td style="padding:10px 0;border-bottom:1px solid var(--line);">{{ $row['value'] }}</td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>

    @can('staff.edit')
    <div class="kia-card" style="max-width:600px;margin-top:20px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('hr.employment_details') }}</h2></div>
        <div class="kia-card-body">
            <table style="width:100%;font-size:.875rem;border-collapse:collapse;">
                @foreach([
                    ['label' => __('hr.contract_type'), 'value' => $staff->contract_type ? __('hr.contract_type_'.$staff->contract_type) : '—'],
                    ['label' => __('hr.contract_end_date'), 'value' => $staff->contract_end_date?->format('d M Y') ?? '—'],
                    ['label' => __('hr.employment_status'), 'value' => __('hr.employment_status_'.$staff->employment_status)],
                ] as $row)
                <tr>
                    <td style="padding:10px 0;color:var(--muted);width:40%;font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--line);">{{ $row['label'] }}</td>
                    <td style="padding:10px 0;border-bottom:1px solid var(--line);">{{ $row['value'] }}</td>
                </tr>
                @endforeach
            </table>
            <p style="color:var(--muted);font-size:.78rem;margin-top:10px;">{{ __('hr.contract_document_hint') }}</p>
        </div>
    </div>
    @endcan

    @include('documents._list', ['documents' => $staff->issuedDocuments])
    @include('staff._qualifications', ['staff' => $staff])
    @include('staff._uploaded_documents', ['staff' => $staff])
    @include('staff._development-logs', ['staff' => $staff])
</x-app-layout>
