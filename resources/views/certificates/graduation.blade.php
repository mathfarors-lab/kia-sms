<x-app-layout>
    <x-slot name="title">{{ __('documents.cert_graduation') }}</x-slot>
    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('documents.cert_graduation') }}</h1>
            <p class="kia-page-sub">{{ $student->student_code }} &mdash; Cert# {{ $certNo }}</p>
        </div>
        <a href="{{ route('certificates.graduation.pdf', $student) }}" class="btn btn-primary">{{ __('documents.download_pdf') }}</a>
    </div>
    <div class="kia-card"><div class="kia-card-body">
        <p>{{ __('documents.graduation_preview_note') }}</p>
        <div style="margin-top:12px;">
            <b>{{ $student->name_en }}</b>
            @if($student->name_km) / {{ $student->name_km }} @endif
            — {{ $student->student_code }}
        </div>
    </div></div>
</x-app-layout>
