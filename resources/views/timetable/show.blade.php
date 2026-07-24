<x-app-layout>
    <x-slot name="title">Timetable — {{ $section->schoolClass->name }} {{ $section->name }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Timetable — {{ $section->schoolClass->name }} {{ $section->name }}</h1>
            <p class="kia-page-sub">{{ $canManage ? 'Click a cell to add or remove a slot' : 'Read-only view' }}</p>
        </div>
        <a href="{{ route('classes.sections.index', $section->schoolClass) }}" class="btn btn-ghost">Back</a>
    </div>

    <div id="clash-error" class="kia-alert kia-alert-danger" style="display:none"></div>

    <div class="kia-card" style="overflow-x:auto">
        <table class="kia-table" style="min-width:760px">
            <thead>
                <tr>
                    <th style="width:60px">Period</th>
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
                    @php $slot = $timetables->get($day . '_' . $period)?->first(); @endphp
                    <td style="padding:.25rem">
                        @if($slot)
                        <div class="kia-slot-filled" data-id="{{ $slot->id }}" style="background:#EDEFFC;border:1px solid var(--royal);border-radius:.5rem;padding:.4rem .6rem;font-size:.8rem;{{ $canManage ? 'cursor:pointer' : '' }}" @if($canManage) onclick="removeSlot({{ $slot->id }}, this)" @endif>
                            <strong>{{ $slot->subject->name_en }}</strong><br>
                            <small>{{ $slot->teacher?->user?->name ?? 'No teacher' }}</small>
                            @if($slot->room)<br><small>{{ $slot->room }}</small>@endif
                        </div>
                        @elseif($canManage)
                        <div class="kia-slot-empty" style="min-height:56px;border:1.5px dashed var(--line);border-radius:.5rem;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);font-size:.75rem"
                             onclick="openModal('{{ $day }}', {{ $period }}, this)">
                            +
                        </div>
                        @else
                        <div style="min-height:56px;border:1.5px dashed var(--line);border-radius:.5rem"></div>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Add Slot Modal --}}
    @if($canManage)
    <div id="slot-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.4);align-items:center;justify-content:center">
        <div class="kia-card" style="width:440px;max-width:95vw;padding:1.5rem">
            <h3 style="margin:0 0 1rem">Add Timetable Slot</h3>
            <form id="slot-form">
                <input type="hidden" id="slot-day">
                <input type="hidden" id="slot-period">
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <select id="slot-subject" class="form-control" required>
                        <option value="">— Select —</option>
                        @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name_en }} ({{ $subject->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Teacher</label>
                    <select id="slot-teacher" class="form-control">
                        <option value="">— None —</option>
                        @foreach($staff as $s)
                        <option value="{{ $s->id }}">{{ $s->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="time" id="slot-start" class="form-control" value="07:00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="time" id="slot-end" class="form-control" value="08:00">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Room</label>
                    <input type="text" id="slot-room" class="form-control" placeholder="Optional">
                </div>
                <div id="slot-error" class="kia-alert kia-alert-danger" style="display:none;margin-bottom:.75rem"></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Slot</button>
                    <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const modal = document.getElementById('slot-modal');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || '{{ csrf_token() }}';
    const storeUrl = '{{ route("timetable.store", $section) }}';
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

        const body = {
            day:        document.getElementById('slot-day').value,
            period:     parseInt(document.getElementById('slot-period').value),
            subject_id: document.getElementById('slot-subject').value,
            teacher_id: document.getElementById('slot-teacher').value || null,
            start_time: document.getElementById('slot-start').value,
            end_time:   document.getElementById('slot-end').value,
            room:       document.getElementById('slot-room').value || null,
        };

        const res = await fetch(storeUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(body),
        });

        const data = await res.json();

        if (!res.ok) {
            errEl.textContent = data.error || 'Error saving slot.';
            errEl.style.display = 'block';
            return;
        }

        // Replace empty cell with filled slot (reload for simplicity)
        window.location.reload();
    });

    async function removeSlot(id, el) {
        if (!confirm('Remove this slot?')) return;
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
