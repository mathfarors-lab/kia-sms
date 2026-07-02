<x-app-layout>
    <x-slot name="title">{{ __('documents.id_card') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('documents.id_card') }} — {{ $student->name_km ?: $student->name_en }}</h1>
            <p class="kia-page-sub">{{ $student->student_code }}</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('id-cards.student.pdf', $student) }}" class="btn btn-primary">{{ __('documents.download_pdf') }}</a>
            <a href="{{ url()->previous() }}" class="btn btn-ghost">{{ __('Back') }}</a>
        </div>
    </div>

    {{-- Card preview --}}
    <div class="kia-card" style="max-width:380px;">
        <div class="kia-card-body">
            <div style="width:323px;height:204px;border:2px solid #2B3A8F;border-radius:10px;overflow:hidden;background:#fff;position:relative;font-family:sans-serif;">
                {{-- Header bar --}}
                <div style="background:#2B3A8F;color:#fff;padding:6px 10px;display:flex;align-items:center;gap:6px;">
                    <div style="font-size:11px;font-weight:bold;letter-spacing:1px;">KIA</div>
                    <div style="font-size:9px;">Khmer Intellectual Academy</div>
                    <div style="margin-left:auto;font-size:8px;color:#ECC531;">{{ $year?->name }}</div>
                </div>
                {{-- Body --}}
                <div style="display:flex;padding:8px;gap:10px;">
                    {{-- Photo --}}
                    <div style="flex-shrink:0;">
                        @if($photoUri)
                            <img src="{{ $photoUri }}" style="width:60px;height:75px;object-fit:cover;border:1px solid #ddd;border-radius:4px;" alt="photo">
                        @else
                            <div style="width:60px;height:75px;background:#e8eaf0;border:1px solid #ddd;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:9px;color:#888;text-align:center;">
                                No<br>Photo
                            </div>
                        @endif
                    </div>
                    {{-- Info --}}
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:12px;font-weight:bold;color:#1b1f33;line-height:1.3;">{{ $student->name_en }}</div>
                        @if($student->name_km)
                        <div style="font-size:11px;color:#2B3A8F;line-height:1.3;">{{ $student->name_km }}</div>
                        @endif
                        <div style="font-size:9px;color:#5b6079;margin-top:4px;">
                            <div><b>ID:</b> {{ $student->student_code }}</div>
                            @if($section)
                            <div><b>Class:</b> {{ $section->schoolClass->name ?? '' }} / {{ $section->name }}</div>
                            @endif
                            <div><b>Gender:</b> {{ ucfirst($student->gender) }}</div>
                        </div>
                    </div>
                    {{-- QR --}}
                    <div style="flex-shrink:0;display:flex;align-items:flex-end;" data-qr-payload="{{ $student->student_code }}">
                        <img src="{{ $qrUri }}" style="width:52px;height:52px;" alt="qr">
                    </div>
                </div>
                {{-- Footer --}}
                <div style="background:#f0f3ff;padding:3px 10px;font-size:8px;color:#5b6079;text-align:center;">
                    STUDENT IDENTIFICATION CARD / អត្តសញ្ញាណប័ណ្ណសិស្ស
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
