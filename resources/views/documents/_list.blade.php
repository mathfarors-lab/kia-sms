@php
    $documentTypeLabels = [
        \App\Models\IssuedDocument::TYPE_ID_CARD         => __('documents.id_card'),
        \App\Models\IssuedDocument::TYPE_ENROLLMENT_CERT => __('documents.cert_enrollment'),
        \App\Models\IssuedDocument::TYPE_GRADUATION_CERT => __('documents.cert_graduation'),
        \App\Models\IssuedDocument::TYPE_LEAVING_CERT    => __('documents.cert_leaving'),
    ];
    $docsSvc = app(\App\Services\DocumentService::class);
@endphp
<div class="kia-card" style="margin-top:20px;">
    <div class="kia-card-header"><h2 class="kia-card-title">{{ __('documents.section_title') }}</h2></div>
    <div class="kia-card-body">
        @forelse($documents as $doc)
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid var(--line);flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:12px;">
                @if($doc->type === \App\Models\IssuedDocument::TYPE_ID_CARD && $doc->student)
                    <a href="{{ route('id-cards.student.show', $doc->student) }}" title="{{ __('documents.view_card') }}">
                        @include('documents._id_card_mini', [
                            'photoUri' => $docsSvc->photoDataUri($doc->student->photo),
                            'qrUri'    => $docsSvc->qrDataUri($doc->student->student_code),
                            'name'     => $doc->student->name_km ?: $doc->student->name_en,
                            'code'     => $doc->student->student_code,
                        ])
                    </a>
                @elseif($doc->type === \App\Models\IssuedDocument::TYPE_ID_CARD && $doc->staff)
                    <a href="{{ route('id-cards.staff.show', $doc->staff) }}" title="{{ __('documents.view_card') }}">
                        @include('documents._id_card_mini', [
                            'photoUri' => $docsSvc->photoDataUri($doc->staff->photo),
                            'qrUri'    => $docsSvc->qrDataUri($doc->staff->staff_code),
                            'name'     => $doc->staff->user->name,
                            'code'     => $doc->staff->staff_code,
                        ])
                    </a>
                @endif
                <div>
                    <div style="font-weight:600;font-size:.875rem;">{{ $documentTypeLabels[$doc->type] ?? $doc->type }}</div>
                    <div style="font-size:.78rem;color:var(--muted);">
                        @if($doc->number)<span class="mono">{{ $doc->number }}</span> &middot; @endif
                        {{ __('documents.issued_on') }} {{ $doc->issued_at->format('d M Y') }}
                    </div>
                </div>
            </div>
            @if($doc->type === \App\Models\IssuedDocument::TYPE_ID_CARD && $doc->student_id)
                {{-- Student card: authorizeStudent() gates on id-cards.generate (+ ownership), so this mirrors it. --}}
                @can('id-cards.generate')
                <a href="{{ route('id-cards.student.pdf', $doc->student_id) }}" class="btn btn-sm btn-outline">{{ __('documents.download_pdf') }}</a>
                @endcan
            @elseif($doc->type === \App\Models\IssuedDocument::TYPE_ID_CARD && $doc->staff_id)
                {{-- Staff card: authorizeStaff() gates on role/ownership, NOT this permission — always show, let the route be the source of truth. --}}
                <a href="{{ route('id-cards.staff.pdf', $doc->staff_id) }}" class="btn btn-sm btn-outline">{{ __('documents.download_pdf') }}</a>
            @elseif($doc->type === \App\Models\IssuedDocument::TYPE_ENROLLMENT_CERT)
                @can('certificates.issue')
                <a href="{{ route('certificates.enrollment.pdf', $doc->student_id) }}" class="btn btn-sm btn-outline">{{ __('documents.download_pdf') }}</a>
                @endcan
            @elseif($doc->type === \App\Models\IssuedDocument::TYPE_GRADUATION_CERT)
                @can('certificates.issue')
                <a href="{{ route('certificates.graduation.pdf', $doc->student_id) }}" class="btn btn-sm btn-outline">{{ __('documents.download_pdf') }}</a>
                @endcan
            @elseif($doc->type === \App\Models\IssuedDocument::TYPE_LEAVING_CERT)
                @can('certificates.issue')
                <a href="{{ route('certificates.leaving.pdf', $doc->student_id) }}" class="btn btn-sm btn-outline">{{ __('documents.download_pdf') }}</a>
                @endcan
            @endif
        </div>
        @empty
        <p style="color:var(--muted);font-size:.875rem;">{{ __('documents.none_issued') }}</p>
        @endforelse
    </div>
</div>
