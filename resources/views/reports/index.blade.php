<x-app-layout>
    <x-slot name="title">Reports</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">Reports</h1>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem">
        <div class="kia-card" style="padding:1.5rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:.5rem">Enrollment Roster</h3>
            <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:1rem">Full list of enrolled students by class and section.</p>
            <form method="GET" action="{{ route('reports.enrollment') }}" style="display:flex;flex-direction:column;gap:.5rem">
                <select name="year_id" class="form-control" required>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}" @selected($y->is_active)>{{ $y->name }}</option>
                    @endforeach
                </select>
                @role('owner')
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--muted);">
                    <input type="checkbox" name="all_branches" value="1"> {{ __('All branches') }}
                </label>
                @endrole
                <div style="display:flex;gap:.5rem">
                    <button name="format" value="pdf" class="btn btn-secondary" style="flex:1">PDF</button>
                    <button name="format" value="excel" class="btn btn-secondary" style="flex:1">CSV</button>
                    <button class="btn btn-primary" style="flex:1">View</button>
                </div>
            </form>
        </div>

        <div class="kia-card" style="padding:1.5rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:.5rem">Attendance Summary</h3>
            <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:1rem">Per-student attendance rates with date range filter.</p>
            <form method="GET" action="{{ route('reports.attendance') }}" style="display:flex;flex-direction:column;gap:.5rem">
                <select name="year_id" class="form-control" required>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}" @selected($y->is_active)>{{ $y->name }}</option>
                    @endforeach
                </select>
                <div style="display:flex;gap:.4rem">
                    <input type="date" name="from" class="form-control" placeholder="From" style="flex:1">
                    <input type="date" name="to" class="form-control" placeholder="To" style="flex:1">
                </div>
                @role('owner')
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--muted);">
                    <input type="checkbox" name="all_branches" value="1"> {{ __('All branches') }}
                </label>
                @endrole
                <div style="display:flex;gap:.5rem">
                    <button name="format" value="pdf" class="btn btn-secondary" style="flex:1">PDF</button>
                    <button class="btn btn-primary" style="flex:1">View</button>
                </div>
            </form>
        </div>

        <div class="kia-card" style="padding:1.5rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:.5rem">Fee Collection</h3>
            <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:1rem">Payments received by date range.</p>
            <form method="GET" action="{{ route('reports.fees') }}" style="display:flex;flex-direction:column;gap:.5rem">
                <select name="year_id" class="form-control" required>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}" @selected($y->is_active)>{{ $y->name }}</option>
                    @endforeach
                </select>
                <div style="display:flex;gap:.4rem">
                    <input type="date" name="from" class="form-control" placeholder="From" style="flex:1">
                    <input type="date" name="to" class="form-control" placeholder="To" style="flex:1">
                </div>
                @role('owner')
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--muted);">
                    <input type="checkbox" name="all_branches" value="1"> {{ __('All branches') }}
                </label>
                @endrole
                <div style="display:flex;gap:.5rem">
                    <button name="format" value="pdf" class="btn btn-secondary" style="flex:1">PDF</button>
                    <button name="format" value="excel" class="btn btn-secondary" style="flex:1">CSV</button>
                    <button class="btn btn-primary" style="flex:1">View</button>
                </div>
            </form>
        </div>

        <div class="kia-card" style="padding:1.5rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:.5rem">{{ __('gate.staff_punctuality') }}</h3>
            <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:1rem">{{ __('gate.staff_punctuality_desc') }}</p>
            <form method="GET" action="{{ route('reports.staff-punctuality') }}" style="display:flex;flex-direction:column;gap:.5rem">
                <input type="month" name="month" class="form-control" value="{{ now()->format('Y-m') }}">
                <div style="display:flex;gap:.5rem">
                    <button name="format" value="pdf" class="btn btn-secondary" style="flex:1">PDF</button>
                    <button name="format" value="excel" class="btn btn-secondary" style="flex:1">CSV</button>
                    <button class="btn btn-primary" style="flex:1">View</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
