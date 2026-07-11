@props(['status'])
@php
    $class = match($status) {
        'enquiry'      => 'pill-muted',
        'applied'      => 'pill-royal',
        'under_review' => 'pill-warn',
        'accepted'     => 'pill-ok',
        'rejected'     => 'pill-bad',
        'converted'    => 'pill-gold',
        default        => 'pill-muted',
    };
@endphp
<span class="pill {{ $class }}">{{ __('admissions.status_' . $status) }}</span>
