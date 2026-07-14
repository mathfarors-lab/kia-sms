<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Services\AnalyticsService;
use App\Services\GateScanService;
use Illuminate\Http\Request;

class GateController extends Controller
{
    public function __construct(private GateScanService $gateScan, private AnalyticsService $analytics) {}

    /** The kiosk page itself — standalone, no app shell (see resources/views/gate/scan.blade.php). */
    public function station()
    {
        $this->authorize('gate.scan');
        return view('gate.scan');
    }

    public function scan(Request $request)
    {
        $this->authorize('gate.scan');

        $data = $request->validate(['code' => ['required', 'string', 'max:100']]);

        $result = $this->gateScan->scan($data['code'], $request->user());
        $entity = $result['entity'];

        return response()->json([
            'result'    => $result['result'],
            'type'      => $result['type'],
            'event'     => $result['event'],
            'name_en'   => $entity?->name_en ?? $entity?->user?->name,
            'name_km'   => $entity?->name_km,
            'photo_url' => $entity ? $this->photoUrl($result['type'], $entity) : null,
        ]);
    }

    /**
     * Polled by the "Arrivals Today" dashboard widget every 15-30s — no
     * websockets. Either permission grants access: analytics.view for
     * admin/principal oversight, gate.scan so the receptionist operating
     * the station can see their own dashboard's widget too (they don't
     * otherwise hold analytics.view).
     */
    public function arrivalsFeed(Request $request)
    {
        abort_unless(
            $request->user()->can('analytics.view') || $request->user()->can('gate.scan'),
            403
        );

        return response()->json($this->analytics->todayArrivalsFeed());
    }

    private function photoUrl(?string $type, $entity): ?string
    {
        return match ($type) {
            'student' => route('students.photo', $entity),
            'staff'   => route('staff.photo', $entity),
            default   => null,
        };
    }
}
