<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Exports\FinanceReportExport;
use App\Services\AnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class FinanceReportController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function dashboard()
    {
        $this->authorize('invoices.view');

        $collectedMonth = $this->analytics->revenueThisMonth();
        $outstanding    = Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])->sum(DB::raw('total - paid'));
        $overdueCount   = Invoice::where('status', 'overdue')->count();

        // Last 7 months trend
        $trend = collect(range(6, 0))->map(function ($months) {
            $start = now()->subMonths($months)->startOfMonth();
            $end   = now()->subMonths($months)->endOfMonth();
            return [
                'label'     => $start->format('M Y'),
                'collected' => Payment::whereBetween('paid_at', [$start, $end])->sum('amount'),
            ];
        });

        $recentPayments = Payment::with(['invoice.student', 'receivedBy'])
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get();

        return view('finance.dashboard', compact(
            'collectedMonth', 'outstanding', 'overdueCount', 'trend', 'recentPayments'
        ));
    }

    public function report(Request $request)
    {
        $this->authorize('invoices.view');

        $byClass = SchoolClass::withCount(['sections as enrolled_count' => function ($q) {
                $q->join('student_section', 'student_section.section_id', '=', 'sections.id');
            }])
            ->get()
            ->map(function ($class) {
                $invoiceIds = Invoice::whereHas('student', function ($q) use ($class) {
                    $q->whereHas('sections', fn ($s) => $s->where('sections.school_class_id', $class->id));
                })->pluck('id');

                return [
                    'class'       => $class->name,
                    'collected'   => Payment::whereIn('invoice_id', $invoiceIds)->sum('amount'),
                    'outstanding' => Invoice::whereIn('id', $invoiceIds)->sum(DB::raw('total - paid')),
                ];
            });

        $byMonth = collect(range(5, 0))->map(function ($months) {
            $start = now()->subMonths($months)->startOfMonth();
            $end   = now()->subMonths($months)->endOfMonth();
            return [
                'month'       => $start->format('M Y'),
                'collected'   => Payment::whereBetween('paid_at', [$start, $end])->sum('amount'),
                'invoiced'    => Invoice::whereBetween('created_at', [$start, $end])->sum('total'),
            ];
        });

        return view('finance.report', compact('byClass', 'byMonth'));
    }

    public function exportExcel()
    {
        $this->authorize('invoices.view');
        return Excel::download(new FinanceReportExport, 'finance-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportPdf()
    {
        $this->authorize('invoices.view');

        $invoices = Invoice::with(['student', 'academicYear'])->latest()->limit(200)->get();
        $pdf = Pdf::loadView('pdf.finance-report', compact('invoices'))
            ->setPaper('a4', 'landscape')
            ->setOptions(['isHtml5ParserEnabled' => true]);

        return $pdf->download('finance-report-' . now()->format('Y-m-d') . '.pdf');
    }
}
