<x-app-layout>
    <x-slot name="title">{{ $application->application_no }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('admissions.index') }}">{{ __('admissions.title') }}</a>
        <span class="sep">/</span>
        <span>{{ $application->application_no }}</span>
    </div>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $application->name_km ?: $application->name_en }}</h1>
            <p class="kia-page-sub">
                <span class="mono" style="color:var(--royal);">{{ $application->application_no }}</span>
                · <x-admission-status-pill :status="$application->status" />
            </p>
        </div>
        <div class="d-flex gap-2">
            @can('admissions.manage')
                @unless($application->isConverted())
                <a href="{{ route('admissions.edit', $application) }}" class="btn btn-outline">{{ __('Edit') }}</a>
                @endunless
            @endcan
        </div>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="kia-alert" style="background:var(--danger-light,#fee2e2);color:var(--danger,#991b1b)">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="kia-alert" style="background:var(--danger-light,#fee2e2);color:var(--danger,#991b1b)">{{ $errors->first() }}</div>@endif

    {{-- Pipeline actions --}}
    @can('admissions.manage')
    @unless($application->isConverted())
    <div class="kia-card" style="margin-bottom:16px;">
        <div class="kia-card-body" style="display:flex;gap:10px;flex-wrap:wrap;">
            @if(!in_array($application->status, ['under_review', 'accepted', 'rejected']))
            <form method="POST" action="{{ route('admissions.status', $application) }}">
                @csrf<input type="hidden" name="status" value="under_review">
                <button class="btn btn-outline" type="submit">{{ __('admissions.mark_under_review') }}</button>
            </form>
            @endif

            @if($application->status !== 'accepted')
            <form method="POST" action="{{ route('admissions.status', $application) }}">
                @csrf<input type="hidden" name="status" value="accepted">
                <button class="btn btn-primary" type="submit">{{ __('admissions.accept') }}</button>
            </form>
            @endif

            @if($application->status !== 'rejected')
            <form method="POST" action="{{ route('admissions.status', $application) }}">
                @csrf<input type="hidden" name="status" value="rejected">
                <button class="btn btn-ghost" style="color:var(--danger);" type="submit">{{ __('admissions.reject') }}</button>
            </form>
            @endif

        </div>
    </div>
    @endunless
    @endcan

    {{-- Prominent, dedicated call-out — pulled out of the generic action row so
         the admissions→student relationship is obvious from the UI itself,
         not just from what convert() happens to do under the hood. --}}
    @can('admissions.manage')
    @if($application->status === 'accepted' && !$application->isConverted())
    <div class="kia-card" style="margin-bottom:16px;border:2px solid var(--ok);background:linear-gradient(0deg, var(--paper), transparent);">
        <div class="kia-card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <div style="flex:1;min-width:240px;">
                <div style="font-weight:700;color:var(--ok);font-size:.95rem;margin-bottom:4px;">✓ {{ __('admissions.ready_to_convert') }}</div>
                <p style="margin:0;color:var(--muted);font-size:.85rem;max-width:60ch;">{{ __('admissions.ready_to_convert_hint') }}</p>
            </div>
            <form method="POST" action="{{ route('admissions.convert', $application) }}"
                  onsubmit="return confirm('{{ __('admissions.convert_confirm') }}')">
                @csrf
                <button class="btn btn-primary" style="background:var(--ok);font-size:.95rem;padding:12px 24px;" type="submit">{{ __('admissions.convert_to_student') }} →</button>
            </form>
        </div>
    </div>
    @endif
    @endcan

    @if($application->isConverted() && $application->student)
    <div class="kia-alert kia-alert-success" style="margin-bottom:16px;">
        {{ __('admissions.status_converted') }}:
        <a href="{{ route('students.show', $application->student) }}" class="mono">{{ $application->student->student_code }}</a>
    </div>
    @endif

    <div class="kia-card" style="max-width:640px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('Details') }}</h2></div>
        <div class="kia-card-body">
            <table style="width:100%;font-size:.875rem;border-collapse:collapse;">
                @foreach([
                    ['label' => __('Full Name (English)'),          'value' => $application->name_en],
                    ['label' => __('Full Name (Khmer)'),            'value' => $application->name_km ?? '—'],
                    ['label' => __('Gender'),                       'value' => ucfirst($application->gender)],
                    ['label' => __('Date of Birth'),                'value' => $application->date_of_birth?->format('d M Y') ?? '—'],
                    ['label' => __('admissions.guardian'),          'value' => $application->guardian_name ?? '—'],
                    ['label' => __('admissions.guardian_phone'),    'value' => $application->guardian_phone ?? '—'],
                    ['label' => __('admissions.guardian_relation'), 'value' => $application->guardian_relation ?? '—'],
                    ['label' => __('admissions.desired_class'),     'value' => $application->desiredClass?->name ?? '—'],
                    ['label' => __('admissions.academic_year'),     'value' => $application->academicYear?->name ?? '—'],
                    ['label' => __('Address'),                      'value' => $application->address ?? '—'],
                    ['label' => __('admissions.notes'),             'value' => $application->notes ?? '—'],
                    ['label' => __('admissions.submitted'),         'value' => $application->created_at->format('d M Y H:i')],
                ] as $row)
                <tr>
                    <td style="padding:9px 0;color:var(--muted);width:38%;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--line);">{{ $row['label'] }}</td>
                    <td style="padding:9px 0;border-bottom:1px solid var(--line);">{{ $row['value'] }}</td>
                </tr>
                @endforeach
                @if($application->document_path)
                <tr>
                    <td style="padding:9px 0;color:var(--muted);font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--line);">{{ __('admissions.document') }}</td>
                    <td style="padding:9px 0;border-bottom:1px solid var(--line);">
                        <a href="{{ route('admissions.document', $application) }}">⬇ {{ $application->document_original_name ?? __('admissions.download_document') }}</a>
                    </td>
                </tr>
                @endif
                @if($application->reviewer)
                <tr>
                    <td style="padding:9px 0;color:var(--muted);font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">{{ __('Reviewed by') }}</td>
                    <td style="padding:9px 0;">{{ $application->reviewer->name }} · {{ $application->reviewed_at?->format('d M Y H:i') }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>
</x-app-layout>
