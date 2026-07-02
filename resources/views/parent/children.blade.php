<x-app-layout>
    <x-slot name="title">{{ __('My Children') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('My Children') }}</h1>
            <p class="kia-page-sub">{{ $children->count() }} {{ __('linked') }}</p>
        </div>
    </div>

    @if($children->isEmpty())
    <div class="kia-card">
        <div class="kia-card-body">
            <div class="kia-empty">
                <h3>{{ __('No children linked') }}</h3>
                <p>{{ __('Contact admin to link your children to your account.') }}</p>
            </div>
        </div>
    </div>
    @else

    @foreach($children as $child)
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="student-initials">{{ strtoupper(substr($child->name_en, 0, 2)) }}</div>
                <div>
                    <div style="font-weight:700;font-size:1rem;">{{ $child->name_km ?: $child->name_en }}</div>
                    @if($child->name_km)
                    <div style="font-size:.82rem;color:var(--muted);">{{ $child->name_en }}</div>
                    @endif
                    <span class="mono" style="font-size:.78rem;color:var(--muted);">{{ $child->student_code }}</span>
                    <span class="pill pill-ok" style="margin-left:8px;">{{ $child->status }}</span>
                </div>
            </div>
            <a href="{{ route('parent.child.show', $child) }}" class="btn btn-sm btn-outline">{{ __('View Details') }}</a>
        </div>
        <div class="kia-card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;">

                {{-- Attendance --}}
                <div style="text-align:center;padding:12px;background:var(--bg-alt);border-radius:8px;">
                    <div style="font-size:1.6rem;font-weight:700;color:{{ ($child->attendance_pct ?? 0) >= 75 ? 'var(--ok)' : 'var(--warn)' }};">
                        {{ $child->attendance_pct !== null ? $child->attendance_pct . '%' : '—' }}
                    </div>
                    <div style="font-size:.8rem;color:var(--muted);margin-top:4px;">{{ __('Attendance') }}</div>
                </div>

                {{-- Unpaid invoices --}}
                <div style="text-align:center;padding:12px;background:var(--bg-alt);border-radius:8px;">
                    <div style="font-size:1.6rem;font-weight:700;color:{{ $child->invoices->count() > 0 ? 'var(--warn)' : 'var(--ok)' }};">
                        {{ $child->invoices->count() }}
                    </div>
                    <div style="font-size:.8rem;color:var(--muted);margin-top:4px;">{{ __('Unpaid Invoices') }}</div>
                </div>

                {{-- Section --}}
                <div style="padding:12px;background:var(--bg-alt);border-radius:8px;">
                    <div style="font-size:.75rem;color:var(--muted);margin-bottom:4px;">{{ __('Current Section') }}</div>
                    @if($child->sections->isNotEmpty())
                        <div style="font-weight:600;">{{ $child->sections->first()->schoolClass->name ?? '—' }}</div>
                        <div style="font-size:.82rem;color:var(--muted);">{{ $child->sections->first()->name }}</div>
                    @else
                        <div style="color:var(--muted);">—</div>
                    @endif
                </div>

            </div>

            @if($child->invoices->isNotEmpty())
            <div style="margin-top:16px;">
                <div style="font-size:.8rem;font-weight:600;color:var(--muted);margin-bottom:8px;">{{ __('Outstanding Invoices') }}</div>
                @foreach($child->invoices as $invoice)
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);">
                    <div>
                        <span class="mono" style="font-size:.82rem;">{{ $invoice->number }}</span>
                        <span style="margin-left:8px;font-size:.8rem;color:var(--muted);">{{ $invoice->term }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span style="font-weight:600;">${{ $invoice->remainingBalance() }}</span>
                        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-xs btn-outline">{{ __('View') }}</a>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if($child->latest_exams->isNotEmpty())
            <div style="margin-top:16px;">
                <div style="font-size:.8rem;font-weight:600;color:var(--muted);margin-bottom:8px;">{{ __('Published Exams') }}</div>
                @foreach($child->latest_exams as $exam)
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);">
                    <span>{{ $exam->name }}</span>
                    <a href="{{ route('report-card.show', [$exam, $child]) }}" class="btn btn-xs btn-ghost">{{ __('Report Card') }}</a>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endforeach

    @if($announcements->isNotEmpty())
    <div class="kia-card">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('Recent Announcements') }}</h2>
        </div>
        <div class="kia-card-body">
            @foreach($announcements as $ann)
            <div style="padding:10px 0;border-bottom:1px solid var(--line);">
                <a href="{{ route('announcements.show', $ann) }}" style="font-weight:600;color:var(--royal);">{{ $ann->title }}</a>
                <div style="font-size:.8rem;color:var(--muted);margin-top:2px;">{{ $ann->published_at->diffForHumans() }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @endif
</x-app-layout>
