<x-app-layout>
    <x-slot name="title">{{ __('Parent Dashboard') }}</x-slot>
    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Parent Dashboard') }}</h1>
    </div>
    <div class="kia-card">
        <div class="kia-card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <h2 class="kia-card-title">{{ __('My Children') }}</h2>
            <a href="{{ route('parent.children') }}" class="btn btn-sm btn-outline">{{ __('View Full Details') }}</a>
        </div>
        <div class="kia-card-body">
            @forelse($children as $child)
            <div class="d-flex align-center gap-3" style="padding:10px 0;border-bottom:1px solid var(--line);">
                <div class="student-initials">{{ strtoupper(substr($child->name_en, 0, 2)) }}</div>
                <div style="flex:1;">
                    <div style="font-weight:600;">{{ $child->name_km ?: $child->name_en }}</div>
                    <span class="mono" style="font-size:.78rem;color:var(--muted);">{{ $child->student_code }}</span>
                    @php
                        $statusPillClass = match($child->status) {
                            'enrolled'    => 'pill-ok',
                            'transferred' => 'pill-royal',
                            'graduated'   => 'pill-gold',
                            'dropped'     => 'pill-muted',
                            default       => 'pill-muted',
                        };
                    @endphp
                    <span class="pill {{ $statusPillClass }}" style="margin-left:8px;">{{ $child->status }}</span>
                </div>
                <a href="{{ route('parent.child.show', $child) }}" class="btn btn-xs btn-ghost">{{ __('Details') }}</a>
            </div>
            @empty
            <div class="kia-empty">
                <h3>{{ __('No children linked') }}</h3>
                <p>{{ __('Contact admin to link your children to your account.') }}</p>
            </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
