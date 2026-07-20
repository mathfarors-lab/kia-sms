<x-app-layout>
    <x-slot name="title">{{ __('timetable.teaching_schedule') }} — {{ $staff->user->name }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('staff.index') }}">{{ __('Staff') }}</a>
        <span class="sep">/</span>
        <a href="{{ route('staff.show', $staff) }}">{{ $staff->user->name }}</a>
        <span class="sep">/</span>
        <span>{{ __('timetable.teaching_schedule') }}</span>
    </div>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('timetable.teaching_schedule') }} — {{ $staff->user->name }}</h1>
            <p class="kia-page-sub">{{ $canManage ? __('timetable.click_to_add_or_remove') : __('timetable.read_only_view') }}</p>
        </div>
        <a href="{{ route('staff.show', $staff) }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    <div class="kia-stats" style="margin-bottom:1rem">
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('timetable.total_periods_per_week') }}</div>
            <div class="kia-stat-value">{{ $totalPeriods }}</div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('timetable.sections_taught') }}</div>
            <div class="kia-stat-value">{{ $sectionsTaught }}</div>
        </div>
    </div>

    <div id="clash-error" class="kia-alert kia-alert-danger" style="display:none"></div>

    <div class="kia-card" style="overflow-x:auto">
        <table class="kia-table" style="min-width:760px">
            <thead>
                <tr>
                    <th style="width:60px">{{ __('timetable.period') }}</th>
                    @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday'] as $day)
                    <th>{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach(range(1, 8) as $period)
                <tr>
                    <td style="font-weight:600;text-align:center">{{ $period }}</td>
                    @foreach(['monday','tuesday','wednesday','thursday','friday'] as $day)
                    @php $slot = $slots->get($day . '_' . $period)?->first(); @endphp
                    <td style="padding:.25rem">
                        @if($slot)
                        <div class="kia-slot-filled" data-id="{{ $slot->id }}" style="background:var(--kia-accent-soft,#eef2ff);border-radius:.5rem;padding:.4rem .6rem;font-size:.8rem;{{ $canManage ? 'cursor:pointer' : '' }}" @if($canManage) onclick="removeSlot({{ $slot->id }}, this)" @endif>
                            <strong>{{ $slot->subject->name_en }}</strong><br>
                            <small>{{ $slot->section->schoolClass->name ?? '' }} {{ $slot->section->name ?? '' }}</small>
                            @if($slot->room)<br><small>{{ $slot->room }}</small>@endif
                        </div>
                        @elseif($canManage)
                        <div class="kia-slot-empty" style="min-height:56px;border:1.5px dashed var(--kia-border,#e2e8f0);border-radius:.5rem;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--kia-text-muted,#94a3b8);font-size:.75rem"
                             onclick="openModal('{{ $day }}', {{ $period }}, this)">
                            +
                        </div>
                        @else
                        <div style="min-height:56px;border:1.5px dashed var(--kia-border,#e2e8f0);border-radius:.5rem"></div>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Add Slot Modal — same shape as the section-side one, except Section
         is now the picked field (this teacher is fixed) instead of Teacher
         (that section was fixed). Posts to the exact same store() endpoint. --}}
    @if($canManage)
    <div id="slot-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.4);align-items:center;justify-content:center">
        <div class="kia-card" style="width:440px;max-width:95vw;padding:1.5rem">
            <h3 style="margin:0 0 1rem">{{ __('timetable.add_slot') }}</h3>
            <form id="slot-form">
                <input type="hidden" id="slot-day">
                <input type="hidden" id="slot-period">
                <div class="form-group">
                    <label class="form-label">{{ __('nav.classes_sections') }}</label>
                    <select id="slot-section" class="form-control" required>
                        <option value="">— {{ __('Select…') }} —</option>
                        @foreach($sections as $s)
                        <option value="{{ $s->id }}">{{ $s->schoolClass->name ?? '' }} {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('nav.subjects') }}</label>
                    <select id="slot-subject" class="form-control" required>
                        <option value="">— {{ __('Select…') }} —</option>
                        @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name_en }} ({{ $subject->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div class="form-group">
                        <label class="form-label">{{ __('Start Time') }}</label>
                        <input type="time" id="slot-start" class="form-control" value="07:00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('End Time') }}</label>
                        <input type="time" id="slot-end" class="form-control" value="08:00">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Room') }}</label>
                    <input type="text" id="slot-room" class="form-control" placeholder="{{ __('Optional') }}">
                </div>
                <div id="slot-error" class="kia-alert kia-alert-danger" style="display:none;margin-bottom:.75rem"></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('timetable.save_slot') }}</button>
                    <button type="button" class="btn btn-ghost" onclick="closeModal()">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const modal = document.getElementById('slot-modal');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || '{{ csrf_token() }}';
    const teacherId = {{ $staff->id }};
    let currentCell = null;

    function openModal(day, period, cell) {
        document.getElementById('slot-day').value = day;
        document.getElementById('slot-period').value = period;
        document.getElementById('slot-error').style.display = 'none';
        currentCell = cell;
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
        currentCell = null;
    }

    document.getElementById('slot-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const errEl = document.getElementById('slot-error');
        errEl.style.display = 'none';

        const sectionId = document.getElementById('slot-section').value;
        if (!sectionId) {
            errEl.textContent = '{{ __('timetable.pick_a_section') }}';
            errEl.style.display = 'block';
            return;
        }

        const body = {
            day:        document.getElementById('slot-day').value,
            period:     parseInt(document.getElementById('slot-period').value),
            subject_id: document.getElementById('slot-subject').value,
            teacher_id: teacherId,
            start_time: document.getElementById('slot-start').value,
            end_time:   document.getElementById('slot-end').value,
            room:       document.getElementById('slot-room').value || null,
        };

        // Same store() endpoint the section-side timetable posts to — just
        // built for whichever section was picked in this modal instead of
        // being fixed by the page's own URL.
        const storeUrl = '/sections/' + sectionId + '/timetable';

        const res = await fetch(storeUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(body),
        });

        const data = await res.json();

        if (!res.ok) {
            errEl.textContent = data.error || '{{ __('timetable.error_saving_slot') }}';
            errEl.style.display = 'block';
            return;
        }

        window.location.reload();
    });

    async function removeSlot(id, el) {
        if (!confirm('{{ __('timetable.confirm_remove_slot') }}')) return;
        const res = await fetch('/timetable/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        if (res.ok) window.location.reload();
    }

    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
    </script>
    @endif
</x-app-layout>
