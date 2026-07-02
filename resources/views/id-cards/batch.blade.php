<x-app-layout>
    <x-slot name="title">{{ __('documents.batch_id_cards') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('documents.batch_id_cards') }}</h1>
            <p class="kia-page-sub">
                {{ $section->schoolClass->name ?? '—' }} / {{ $section->name }}
                &mdash; {{ $cards->count() }} {{ __('documents.cards') }}
            </p>
        </div>
        <a href="{{ route('id-cards.batch.pdf', $section) }}" class="btn btn-primary">{{ __('documents.download_batch_pdf') }}</a>
    </div>

    <div class="kia-card">
        <div class="kia-card-body">
            <div style="display:flex;flex-wrap:wrap;gap:12px;">
                @foreach($cards as $card)
                <div style="width:243px;height:153px;border:1px dashed #aaa;border-radius:8px;overflow:hidden;background:#fff;font-family:sans-serif;flex-shrink:0;"
                     data-student-code="{{ $card['student']->student_code }}">
                    <div style="background:#2B3A8F;color:#fff;padding:4px 8px;font-size:9px;font-weight:bold;">
                        KIA — Khmer Intellectual Academy
                        <span style="float:right;font-size:8px;color:#ECC531;">{{ $card['year']?->name }}</span>
                    </div>
                    <div style="display:flex;padding:6px;gap:8px;">
                        @if($card['photoUri'])
                            <img src="{{ $card['photoUri'] }}" style="width:50px;height:63px;object-fit:cover;border:1px solid #ddd;border-radius:3px;" alt="photo">
                        @else
                            <div style="width:50px;height:63px;background:#e8eaf0;border:1px solid #ddd;border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:8px;color:#888;text-align:center;">No<br>Photo</div>
                        @endif
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:10px;font-weight:bold;color:#1b1f33;">{{ $card['student']->name_en }}</div>
                            @if($card['student']->name_km)
                            <div style="font-size:9px;color:#2B3A8F;">{{ $card['student']->name_km }}</div>
                            @endif
                            <div style="font-size:8px;color:#5b6079;margin-top:3px;">
                                <div>ID: {{ $card['student']->student_code }}</div>
                                <div>{{ $card['section']->schoolClass->name ?? '' }} / {{ $card['section']->name }}</div>
                            </div>
                        </div>
                        <div data-qr-payload="{{ $card['student']->student_code }}">
                            <img src="{{ $card['qrUri'] }}" style="width:44px;height:44px;" alt="qr">
                        </div>
                    </div>
                    <div style="background:#f0f3ff;padding:2px 8px;font-size:7px;color:#5b6079;text-align:center;">
                        STUDENT IDENTIFICATION CARD
                    </div>
                </div>
                @endforeach
            </div>

            @if($cards->isEmpty())
            <p class="text-muted">{{ __('documents.no_students_in_section') }}</p>
            @endif
        </div>
    </div>
</x-app-layout>
