<x-app-layout>
    <x-slot name="title">{{ __('documents.id_card') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('documents.id_card') }} — {{ $staff->user->name }}</h1>
            <p class="kia-page-sub">{{ $staff->staff_code }}</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('id-cards.staff.pdf', $staff) }}" class="btn btn-primary">{{ __('documents.download_pdf') }}</a>
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
                        <div style="font-size:12px;font-weight:bold;color:#1b1f33;line-height:1.3;">{{ $staff->user->name }}</div>
                        <div style="font-size:9px;color:#5b6079;margin-top:4px;">
                            <div><b>ID:</b> {{ $staff->staff_code }}</div>
                            @if($staff->position)
                            <div><b>Position:</b> {{ $staff->position }}</div>
                            @endif
                            @if($staff->department)
                            <div><b>Dept:</b> {{ $staff->department }}</div>
                            @endif
                        </div>
                    </div>
                    {{-- QR --}}
                    <div style="flex-shrink:0;display:flex;align-items:flex-end;" data-qr-payload="{{ $staff->staff_code }}">
                        <img src="{{ $qrUri }}" style="width:52px;height:52px;" alt="qr">
                    </div>
                </div>
                {{-- Footer --}}
                <div style="background:#f0f3ff;padding:3px 10px;font-size:8px;color:#5b6079;text-align:center;">
                    STAFF IDENTIFICATION CARD / អត្តសញ្ញាណប័ណ្ណបុគ្គលិក
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
