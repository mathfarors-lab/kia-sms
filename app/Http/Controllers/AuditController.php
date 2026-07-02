<?php

namespace App\Http\Controllers;

use App\Support\Permissions;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(Permissions::AUDIT_VIEW);

        $query = Activity::query()
            ->with('causer')
            ->when($request->causer_id, fn ($q) =>
                $q->where('causer_type', \App\Models\User::class)
                  ->where('causer_id', $request->causer_id)
            )
            ->when($request->log_name, fn ($q) =>
                $q->where('log_name', $request->log_name)
            )
            ->when($request->date_from, fn ($q) =>
                $q->whereDate('created_at', '>=', $request->date_from)
            )
            ->when($request->date_to, fn ($q) =>
                $q->whereDate('created_at', '<=', $request->date_to)
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $logNames = Activity::select('log_name')->distinct()->orderBy('log_name')->pluck('log_name');
        $causers  = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('audit.index', compact('query', 'logNames', 'causers'));
    }
}
