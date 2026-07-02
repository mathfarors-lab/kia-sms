<x-app-layout>
    <x-slot name="title">Student Transport</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Student Transport</h1>
            @if($year)
                <p class="kia-page-sub">{{ $year->name }}</p>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    @can('manage', \App\Models\TransportRoute::class)
        <div class="kia-card" style="max-width:640px;margin-bottom:1rem">
            <div class="kia-card-header"><h3 class="kia-card-title">Assign Student</h3></div>
            <div class="kia-card-body">
                <form method="POST" action="{{ route('transport.students.assign') }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Student *</label>
                            <select name="student_id" class="form-control @error('student_id') is-invalid @enderror" required>
                                <option value="">— Select —</option>
                                @foreach($students as $s)
                                    <option value="{{ $s->id }}">{{ $s->name_en }}</option>
                                @endforeach
                            </select>
                            @error('student_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Vehicle *</label>
                            <select name="vehicle_id" class="form-control @error('vehicle_id') is-invalid @enderror" required>
                                <option value="">— Select —</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}">{{ $v->route->name }} – {{ $v->plate_no }} (cap: {{ $v->capacity }})</option>
                                @endforeach
                            </select>
                            @error('vehicle_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Assign</button>
                </form>
            </div>
        </div>
    @endcan

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr><th>Student</th><th>Route</th><th>Vehicle</th><th>Enrolled</th><th></th></tr></thead>
                <tbody>
                @forelse($assigned as $st)
                    <tr>
                        <td>{{ $st->student->name_en }}</td>
                        <td>{{ $st->route->name }}</td>
                        <td>{{ $st->vehicle->plate_no }}</td>
                        <td>{{ $st->enrolled_at->format('d M Y') }}</td>
                        <td>
                            @can('manage', \App\Models\TransportRoute::class)
                                <form method="POST" action="{{ route('transport.students.remove', $st->student) }}"
                                      onsubmit="return confirm('Remove this assignment?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger" style="font-size:.75rem;padding:.25rem .6rem" type="submit">Remove</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted)">No students assigned.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $assigned->links() }}</div>
    </div>
</x-app-layout>
