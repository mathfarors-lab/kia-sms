<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentTransport;
use App\Models\TransportRoute;
use App\Models\Vehicle;
use App\Services\TransportService;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    public function __construct(private TransportService $transport) {}

    // Routes
    public function routesIndex()
    {
        $this->authorize('viewAny', TransportRoute::class);
        $routes = TransportRoute::withCount('vehicles')->orderBy('name')->paginate(20);
        return view('transport.routes.index', compact('routes'));
    }

    public function routesCreate()
    {
        $this->authorize('manage', TransportRoute::class);
        return view('transport.routes.create');
    }

    public function routesStore(Request $request)
    {
        $this->authorize('manage', TransportRoute::class);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fare'        => ['required', 'numeric', 'min:0'],
        ]);

        TransportRoute::create($data);

        return redirect()->route('transport.routes.index')
            ->with('success', __('engagement.route_saved'));
    }

    public function routesEdit(TransportRoute $route)
    {
        $this->authorize('manage', $route);
        return view('transport.routes.edit', compact('route'));
    }

    public function routesUpdate(Request $request, TransportRoute $route)
    {
        $this->authorize('manage', $route);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fare'        => ['required', 'numeric', 'min:0'],
            'is_active'   => ['boolean'],
        ]);

        $route->update($data);

        return redirect()->route('transport.routes.index')
            ->with('success', __('engagement.route_saved'));
    }

    // Vehicles
    public function vehiclesCreate(TransportRoute $route)
    {
        $this->authorize('manage', $route);
        return view('transport.vehicles.create', compact('route'));
    }

    public function vehiclesStore(Request $request, TransportRoute $route)
    {
        $this->authorize('manage', $route);

        $data = $request->validate([
            'plate_no'    => ['required', 'string', 'max:20'],
            'driver_name' => ['required', 'string', 'max:100'],
            'driver_phone'=> ['nullable', 'string', 'max:20'],
            'capacity'    => ['required', 'integer', 'min:1'],
        ]);

        $data['route_id'] = $route->id;
        Vehicle::create($data);

        return redirect()->route('transport.routes.index')
            ->with('success', __('engagement.vehicle_saved'));
    }

    // Student assignment
    public function studentsIndex(Request $request)
    {
        $this->authorize('viewAny', TransportRoute::class);

        $year     = AcademicYear::where('is_active', true)->first();
        $assigned = StudentTransport::with(['student', 'vehicle.route', 'route'])
            ->where('academic_year_id', $year?->id)
            ->paginate(25);

        $vehicles = Vehicle::with('route')->get();
        $students = Student::orderBy('name_en')->get();

        return view('transport.students', compact('assigned', 'vehicles', 'students', 'year'));
    }

    public function studentsAssign(Request $request)
    {
        $this->authorize('manage', TransportRoute::class);

        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
        ]);

        $year    = AcademicYear::where('is_active', true)->firstOrFail();
        $student = Student::findOrFail($data['student_id']);
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        $this->transport->assign($student, $vehicle, $year);

        return redirect()->route('transport.students')
            ->with('success', __('engagement.student_assigned_transport'));
    }

    public function studentsRemove(Request $request, Student $student)
    {
        $this->authorize('manage', TransportRoute::class);

        $year = AcademicYear::where('is_active', true)->firstOrFail();
        $this->transport->unassign($student, $year);

        return redirect()->route('transport.students')
            ->with('success', __('engagement.transport_removed'));
    }
}
