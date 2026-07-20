<x-app-layout>
    <x-slot name="title">{{ $student->name_en }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('students.index') }}">{{ __('Students') }}</a>
        <span class="sep">/</span>
        <span>{{ $student->name_en }}</span>
    </div>

    <div class="kia-page-header">
        <div class="d-flex align-center gap-3">
            @if($student->photo)
                <img src="{{ route('students.photo', $student) }}" class="photo-preview" style="width:64px;height:64px;border-radius:50%;" alt="">
            @else
                <div class="student-initials" style="width:64px;height:64px;font-size:1.3rem;">{{ strtoupper(substr($student->name_en, 0, 2)) }}</div>
            @endif
            <div>
                <h1 class="kia-page-title" style="margin-bottom:2px;">{{ $student->name_km ?: $student->name_en }}</h1>
                <div style="color:var(--muted);font-size:.875rem;">{{ $student->name_en }}</div>
                <span class="mono" style="font-size:.82rem;color:var(--royal);">{{ $student->student_code }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('students.edit')
            <a href="{{ route('students.edit', $student) }}" class="btn btn-outline">{{ __('Edit') }}</a>
            @endcan
            @can('students.delete')
            <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('{{ __('Delete this student?') }}')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
            </form>
            @endcan
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;flex-wrap:wrap;">
        <div class="kia-card">
            <div class="kia-card-header"><h2 class="kia-card-title">{{ __('Personal Details') }}</h2></div>
            <div class="kia-card-body">
                <table style="width:100%;font-size:.875rem;border-collapse:collapse;">
                    @foreach([
                        ['label' => __('Status'),        'value' => '<span class="pill '.match($student->status){'enrolled'=>'pill-ok','transferred'=>'pill-warn','graduated'=>'pill-royal',default=>'pill-bad'}.'">'.ucfirst($student->status).'</span>'],
                        ['label' => __('Gender'),        'value' => ucfirst($student->gender)],
                        ['label' => __('Date of Birth'), 'value' => $student->date_of_birth?->format('d M Y') ?? '—'],
                        ['label' => __('Age'),           'value' => $student->age ? $student->age . ' ' . __('years') : '—'],
                        ['label' => __('Address'),       'value' => e($student->address) ?? '—'],
                    ] as $row)
                    <tr>
                        <td style="padding:8px 0;color:var(--muted);width:40%;font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;">{{ $row['label'] }}</td>
                        <td style="padding:8px 0;">{!! $row['value'] !!}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>

        <div class="kia-card">
            <div class="kia-card-header"><h2 class="kia-card-title">{{ __('Guardians') }}</h2></div>
            <div class="kia-card-body">
                @forelse($student->guardians as $guardian)
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--line);">
                    <div class="kia-avatar" style="width:32px;height:32px;font-size:.75rem;">{{ strtoupper(substr($guardian->name, 0, 2)) }}</div>
                    <div>
                        <div style="font-weight:600;font-size:.875rem;">{{ $guardian->name }}</div>
                        <div style="font-size:.78rem;color:var(--muted);">{{ $guardian->pivot->relation }}{{ $guardian->pivot->is_primary ? ' · ' . __('Primary') : '' }}</div>
                    </div>
                </div>
                @empty
                <p style="color:var(--muted);font-size:.875rem;">{{ __('No guardians linked.') }}</p>
                @if($student->admissionApplication?->guardian_name)
                <div style="background:var(--paper);border-radius:8px;padding:10px 12px;margin-top:8px;font-size:.82rem;">
                    <div style="font-weight:600;color:var(--muted);text-transform:uppercase;font-size:.7rem;letter-spacing:.04em;margin-bottom:4px;">{{ __('admissions.guardian_on_file') }}</div>
                    <div>{{ $student->admissionApplication->guardian_name }}</div>
                    <div style="color:var(--muted);">
                        {{ $student->admissionApplication->guardian_phone ?? '—' }}
                        @if($student->admissionApplication->guardian_relation) &middot; {{ $student->admissionApplication->guardian_relation }} @endif
                    </div>
                </div>
                @endif
                @endforelse
            </div>
        </div>
    </div>

    @if(auth()->user()->hasRole(['admin', 'accountant', 'principal']))
    <div class="kia-card" style="margin-top:20px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('documents.billing_statement') }}</h2></div>
        <div class="kia-card-body">
            <a href="{{ route('billing-statement.show', $student) }}" class="btn btn-outline">{{ __('documents.billing_statement') }}</a>
        </div>
    </div>
    @endif

    @include('documents._list', ['documents' => $student->issuedDocuments])
    @include('students._uploaded_documents', ['student' => $student])
</x-app-layout>
