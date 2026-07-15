{{-- Compact visual thumbnail of an ID card, used inline in the Documents
     section. Not the same markup as the full-size preview pages
     (id-cards/student.blade.php, id-cards/staff.blade.php) — this is a
     purpose-built small card, not those scaled down, so it can't drift
     out of sync with anything those pages are tested against. --}}
<div style="display:flex;align-items:center;gap:8px;background:#fff;border:1.5px solid #2B3A8F;border-radius:8px;padding:6px 8px;width:190px;flex-shrink:0;" data-qr-payload="{{ $code }}">
    @if($photoUri)
        <img src="{{ $photoUri }}" style="width:34px;height:42px;object-fit:cover;border-radius:3px;flex-shrink:0;" alt="">
    @else
        <div style="width:34px;height:42px;background:#e8eaf0;border-radius:3px;flex-shrink:0;"></div>
    @endif
    <div style="min-width:0;flex:1;">
        <div style="font-size:.7rem;font-weight:700;color:#1b1f33;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $name }}</div>
        <div style="font-size:.62rem;color:#5b6079;" class="mono">{{ $code }}</div>
    </div>
    <img src="{{ $qrUri }}" style="width:28px;height:28px;flex-shrink:0;" alt="">
</div>
