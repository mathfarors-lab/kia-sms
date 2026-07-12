<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\BakongCallback;
use App\Models\BakongFailedVerification;
use App\Models\Branch;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Every query here uses DB::table() for performance, which the Eloquent
 * BranchScope cannot reach — every method filters explicitly via
 * BranchContext::apply() and every cache key includes the branch suffix.
 * A branch-scoped user must only ever see their own branch's figures.
 */
class AnalyticsService
{
    public function overview(AcademicYear $year, int $ttl = 300): array
    {
        $key = "analytics:overview:{$year->id}:" . BranchContext::cacheKeySuffix();

        $cached = Cache::remember($key, $ttl, function () use ($year) {
            $enrolledCount = BranchContext::apply(
                DB::table('student_section')
                    ->join('students', 'students.id', '=', 'student_section.student_id')
                    ->where('student_section.academic_year_id', $year->id),
                'students.branch_id'
            )->distinct('student_section.student_id')->count('student_section.student_id');

            $attendanceSummary = BranchContext::apply(
                DB::table('attendances')
                    ->join('student_section', function ($j) {
                        $j->on('student_section.student_id', '=', 'attendances.student_id')
                          ->on('student_section.section_id', '=', 'attendances.section_id');
                    })
                    ->where('student_section.academic_year_id', $year->id),
                'attendances.branch_id'
            )->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent
                ")
                ->first();

            $attendanceRate = $attendanceSummary->total > 0
                ? round(($attendanceSummary->present / $attendanceSummary->total) * 100, 1)
                : null;

            $feeCollection = BranchContext::apply(
                DB::table('payments')
                    ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
                    ->where('invoices.academic_year_id', $year->id),
                'payments.branch_id'
            )->sum('payments.amount') ?? 0;

            $feeOutstanding = BranchContext::apply(
                DB::table('invoices')
                    ->where('academic_year_id', $year->id)
                    ->where('status', '!=', 'paid')
            )->sum('total') ?? 0;

            $pendingLeaves = BranchContext::apply(
                DB::table('leaves')->where('status', 'pending')
            )->count();

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
        // Bakong verification failures are platform-level (not branch data), so they stay unscoped.
        $failedBakong24h   = BakongFailedVerification::where('created_at', '>=', now()->subDay())->count();
        $failedBakong7d    = BakongFailedVerification::where('created_at', '>=', now()->subWeek())->count();
        $flaggedCallbacks  = BakongCallback::whereNotNull('flag_reason')->count();

        return array_merge($cached, compact('failedBakong24h', 'failedBakong7d', 'flaggedCallbacks'));
    }

    /** Calendar-month revenue (not academic-year scoped) — same figure the finance dashboard shows. */
    public function revenueThisMonth(int $ttl = 180): float
    {
        $key = 'analytics:revenue-this-month:' . now()->format('Y-m') . ':' . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () {
            return (float) (BranchContext::apply(
                DB::table('payments')->where('paid_at', '>=', now()->startOfMonth())
            )->sum('amount') ?? 0);
        });
    }

    /** Same figure the finance dashboard shows: sum of (total - paid) across not-yet-fully-paid invoices. */
    public function outstandingTotal(int $ttl = 180): float
    {
        $key = 'analytics:outstanding-total:' . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () {
            return (float) (BranchContext::apply(
                DB::table('invoices')->whereIn('status', ['unpaid', 'partial', 'overdue'])
            )->sum(DB::raw('total - paid')) ?? 0);
        });
    }

    /** Invoices explicitly flagged overdue (status = 'overdue') — same figure the finance dashboard shows. */
    public function overdueInvoiceCount(int $ttl = 180): int
    {
        $key = 'analytics:overdue-invoice-count:' . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () {
            return BranchContext::apply(DB::table('invoices')->where('status', 'overdue'))->count();
        });
    }

    /** Book issues past due date and not yet returned. */
    public function overdueBooksCount(int $ttl = 180): int
    {
        $key = 'analytics:overdue-books-count:' . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () {
            return BranchContext::apply(
                DB::table('book_issues')
                    ->whereNull('returned_at')
                    ->where('due_date', '<', now()->toDateString())
            )->count();
        });
    }

    public function totalBooksCount(int $ttl = 180): int
    {
        $key = 'analytics:total-books-count:' . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, fn () => BranchContext::apply(DB::table('books'))->count());
    }

    public function booksCurrentlyIssuedCount(int $ttl = 180): int
    {
        $key = 'analytics:books-currently-issued:' . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, fn () =>
            BranchContext::apply(DB::table('book_issues')->whereNull('returned_at'))->count()
        );
    }

    /** Today's present-rate across all sections for the given year. Null when no attendance taken yet today. */
    public function attendanceRateToday(AcademicYear $year, int $ttl = 180): ?float
    {
        $key = "analytics:attendance-today:{$year->id}:" . now()->toDateString() . ':' . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () use ($year) {
            $summary = BranchContext::apply(
                DB::table('attendances')
                    ->join('student_section', function ($j) {
                        $j->on('student_section.student_id', '=', 'attendances.student_id')
                          ->on('student_section.section_id', '=', 'attendances.section_id');
                    })
                    ->where('student_section.academic_year_id', $year->id)
                    ->whereDate('attendances.date', today()),
                'attendances.branch_id'
            )->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present
                ")
                ->first();

            return $summary->total > 0 ? round($summary->present / $summary->total * 100, 1) : null;
        });
    }

    public function attendanceByMonth(AcademicYear $year): array
    {
        $key = "analytics:attendance-by-month:{$year->id}:" . BranchContext::cacheKeySuffix();

        return Cache::remember($key, 600, function () use ($year) {
            $monthExpr = $this->monthExpr('attendances.date');

            return BranchContext::apply(
                DB::table('attendances')
                    ->join('student_section', function ($j) {
                        $j->on('student_section.student_id', '=', 'attendances.student_id')
                          ->on('student_section.section_id', '=', 'attendances.section_id');
                    })
                    ->where('student_section.academic_year_id', $year->id),
                'attendances.branch_id'
            )->selectRaw("
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
        $key = "analytics:fee-by-month:{$year->id}:" . BranchContext::cacheKeySuffix();

        return Cache::remember($key, 600, function () use ($year) {
            $monthExpr = $this->monthExpr('payments.created_at');

            return BranchContext::apply(
                DB::table('payments')
                    ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
                    ->where('invoices.academic_year_id', $year->id),
                'payments.branch_id'
            )->selectRaw("
                    {$monthExpr} as month,
                    SUM(payments.amount) as collected
                ")
                ->groupByRaw($monthExpr)
                ->orderBy('month')
                ->get()
                ->toArray();
        });
    }

    /**
     * Owner-only consolidated comparison: one row per branch, side by side.
     * Every other method in this class filters TO the active branch via
     * BranchContext::apply() — this is the one legitimate place that must
     * NOT do that. It groups BY branch_id instead, deliberately reading
     * across every branch regardless of the caller's own BranchContext.
     * Callers must still gate this behind hasRole('owner') themselves —
     * this method has no way to know who's asking.
     */
    public function perBranchOverview(AcademicYear $year, int $ttl = 180): array
    {
        $key = "analytics:per-branch-overview:{$year->id}";

        return Cache::remember($key, $ttl, function () use ($year) {
            $branches = Branch::orderBy('id')->get()->keyBy('id');

            $enrolled = DB::table('student_section')
                ->join('students', 'students.id', '=', 'student_section.student_id')
                ->where('student_section.academic_year_id', $year->id)
                ->select('students.branch_id', DB::raw('COUNT(DISTINCT student_section.student_id) as total'))
                ->groupBy('students.branch_id')
                ->pluck('total', 'branch_id');

            $revenue = DB::table('payments')
                ->where('paid_at', '>=', now()->startOfMonth())
                ->select('branch_id', DB::raw('SUM(amount) as total'))
                ->groupBy('branch_id')
                ->pluck('total', 'branch_id');

            $attendance = DB::table('attendances')
                ->join('student_section', function ($j) {
                    $j->on('student_section.student_id', '=', 'attendances.student_id')
                      ->on('student_section.section_id', '=', 'attendances.section_id');
                })
                ->where('student_section.academic_year_id', $year->id)
                ->select(
                    'attendances.branch_id',
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present")
                )
                ->groupBy('attendances.branch_id')
                ->get()
                ->keyBy('branch_id');

            $outstanding = DB::table('invoices')
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->select('branch_id', DB::raw('SUM(total - paid) as total'))
                ->groupBy('branch_id')
                ->pluck('total', 'branch_id');

            return $branches->map(function (Branch $branch) use ($enrolled, $revenue, $attendance, $outstanding) {
                $att = $attendance->get($branch->id);

                return [
                    'branch'      => $branch,
                    'enrolled'    => (int) ($enrolled[$branch->id] ?? 0),
                    'revenue'     => (float) ($revenue[$branch->id] ?? 0),
                    'outstanding' => (float) ($outstanding[$branch->id] ?? 0),
                    'attendance_rate' => ($att && $att->total > 0)
                        ? round($att->present / $att->total * 100, 1)
                        : null,
                ];
            })->values()->all();
        });
    }

    private function monthExpr(string $column): string
    {
        return DB::connection()->getDriverName() === 'mysql'
            ? "DATE_FORMAT({$column}, '%Y-%m')"
            : "strftime('%Y-%m', {$column})";
    }
}
