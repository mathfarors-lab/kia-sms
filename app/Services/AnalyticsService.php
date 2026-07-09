<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\BakongCallback;
use App\Models\BakongFailedVerification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function overview(AcademicYear $year, int $ttl = 300): array
    {
        $key = "analytics:overview:{$year->id}";

        $cached = Cache::remember($key, $ttl, function () use ($year) {
            $enrolledCount = DB::table('student_section')
                ->where('academic_year_id', $year->id)
                ->distinct('student_id')
                ->count('student_id');

            $attendanceSummary = DB::table('attendances')
                ->join('student_section', function ($j) {
                    $j->on('student_section.student_id', '=', 'attendances.student_id')
                      ->on('student_section.section_id', '=', 'attendances.section_id');
                })
                ->where('student_section.academic_year_id', $year->id)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent
                ")
                ->first();

            $attendanceRate = $attendanceSummary->total > 0
                ? round(($attendanceSummary->present / $attendanceSummary->total) * 100, 1)
                : null;

            $feeCollection = DB::table('payments')
                ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
                ->where('invoices.academic_year_id', $year->id)
                ->sum('payments.amount') ?? 0;

            $feeOutstanding = DB::table('invoices')
                ->where('academic_year_id', $year->id)
                ->where('status', '!=', 'paid')
                ->sum('total') ?? 0;

            $pendingLeaves = DB::table('leaves')->where('status', 'pending')->count();

            $overdueBooks = $this->overdueBooksCount();

            return compact(
                'enrolledCount',
                'attendanceRate',
                'feeCollection',
                'feeOutstanding',
                'pendingLeaves',
                'overdueBooks'
            );
        });

        // These counts are intentionally NOT cached — attacks/misconfig must surface immediately.
        $failedBakong24h   = BakongFailedVerification::where('created_at', '>=', now()->subDay())->count();
        $failedBakong7d    = BakongFailedVerification::where('created_at', '>=', now()->subWeek())->count();
        $flaggedCallbacks  = BakongCallback::whereNotNull('flag_reason')->count();

        return array_merge($cached, compact('failedBakong24h', 'failedBakong7d', 'flaggedCallbacks'));
    }

    /** Calendar-month revenue (not academic-year scoped) — same figure the finance dashboard shows. */
    public function revenueThisMonth(int $ttl = 180): float
    {
        $key = 'analytics:revenue-this-month:' . now()->format('Y-m');

        return Cache::remember($key, $ttl, function () {
            return (float) (DB::table('payments')
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount') ?? 0);
        });
    }

    /** Same figure the finance dashboard shows: sum of (total - paid) across not-yet-fully-paid invoices. */
    public function outstandingTotal(int $ttl = 180): float
    {
        return Cache::remember('analytics:outstanding-total', $ttl, function () {
            return (float) (DB::table('invoices')
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->sum(DB::raw('total - paid')) ?? 0);
        });
    }

    /** Invoices explicitly flagged overdue (status = 'overdue') — same figure the finance dashboard shows. */
    public function overdueInvoiceCount(int $ttl = 180): int
    {
        return Cache::remember('analytics:overdue-invoice-count', $ttl, function () {
            return DB::table('invoices')->where('status', 'overdue')->count();
        });
    }

    /** Book issues past due date and not yet returned. */
    public function overdueBooksCount(int $ttl = 180): int
    {
        return Cache::remember('analytics:overdue-books-count', $ttl, function () {
            return DB::table('book_issues')
                ->whereNull('returned_at')
                ->where('due_date', '<', now()->toDateString())
                ->count();
        });
    }

    public function totalBooksCount(int $ttl = 180): int
    {
        return Cache::remember('analytics:total-books-count', $ttl, fn () => DB::table('books')->count());
    }

    public function booksCurrentlyIssuedCount(int $ttl = 180): int
    {
        return Cache::remember('analytics:books-currently-issued', $ttl, fn () =>
            DB::table('book_issues')->whereNull('returned_at')->count()
        );
    }

    /** Today's present-rate across all sections for the given year. Null when no attendance taken yet today. */
    public function attendanceRateToday(AcademicYear $year, int $ttl = 180): ?float
    {
        $key = "analytics:attendance-today:{$year->id}:" . now()->toDateString();

        return Cache::remember($key, $ttl, function () use ($year) {
            $summary = DB::table('attendances')
                ->join('student_section', function ($j) {
                    $j->on('student_section.student_id', '=', 'attendances.student_id')
                      ->on('student_section.section_id', '=', 'attendances.section_id');
                })
                ->where('student_section.academic_year_id', $year->id)
                ->whereDate('attendances.date', today())
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present
                ")
                ->first();

            return $summary->total > 0 ? round($summary->present / $summary->total * 100, 1) : null;
        });
    }

    public function attendanceByMonth(AcademicYear $year): array
    {
        $key = "analytics:attendance-by-month:{$year->id}";

        return Cache::remember($key, 600, function () use ($year) {
            $monthExpr = $this->monthExpr('attendances.date');

            return DB::table('attendances')
                ->join('student_section', function ($j) {
                    $j->on('student_section.student_id', '=', 'attendances.student_id')
                      ->on('student_section.section_id', '=', 'attendances.section_id');
                })
                ->where('student_section.academic_year_id', $year->id)
                ->selectRaw("
                    {$monthExpr} as month,
                    COUNT(*) as total,
                    SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present
                ")
                ->groupByRaw($monthExpr)
                ->orderBy('month')
                ->get()
                ->map(fn($row) => [
                    'month'   => $row->month,
                    'total'   => $row->total,
                    'present' => $row->present,
                    'rate'    => $row->total > 0 ? round($row->present / $row->total * 100, 1) : 0,
                ])
                ->toArray();
        });
    }

    public function feeByMonth(AcademicYear $year): array
    {
        $key = "analytics:fee-by-month:{$year->id}";

        return Cache::remember($key, 600, function () use ($year) {
            $monthExpr = $this->monthExpr('payments.created_at');

            return DB::table('payments')
                ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
                ->where('invoices.academic_year_id', $year->id)
                ->selectRaw("
                    {$monthExpr} as month,
                    SUM(payments.amount) as collected
                ")
                ->groupByRaw($monthExpr)
                ->orderBy('month')
                ->get()
                ->toArray();
        });
    }

    private function monthExpr(string $column): string
    {
        return DB::connection()->getDriverName() === 'mysql'
            ? "DATE_FORMAT({$column}, '%Y-%m')"
            : "strftime('%Y-%m', {$column})";
    }
}
