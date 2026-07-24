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

    /** Daily present-rate for the last 7 days (today inclusive) — feeds the admin dashboard trend chart. */
    public function attendanceTrendLast7Days(AcademicYear $year, int $ttl = 300): array
    {
        $key = "analytics:attendance-trend-7d:{$year->id}:" . now()->toDateString() . ':' . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () use ($year) {
            $start = now()->subDays(6)->startOfDay();

            $rows = BranchContext::apply(
                DB::table('attendances')
                    ->join('student_section', function ($j) {
                        $j->on('student_section.student_id', '=', 'attendances.student_id')
                          ->on('student_section.section_id', '=', 'attendances.section_id');
                    })
                    ->where('student_section.academic_year_id', $year->id)
                    ->where('attendances.date', '>=', $start->toDateString()),
                'attendances.branch_id'
            )->selectRaw("
                    attendances.date as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present
                ")
                ->groupBy('attendances.date')
                ->get()
                ->keyBy(fn ($row) => \Illuminate\Support\Carbon::parse($row->date)->toDateString());

            $trend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $row  = $rows->get($date->toDateString());

                $trend[] = [
                    'label' => $date->format('M d'),
                    'rate'  => ($row && $row->total > 0) ? round($row->present / $row->total * 100, 1) : 0,
                ];
            }

            return $trend;
        });
    }

    /** Active-enrolled student count per class, in class display order — feeds the admin dashboard enrollment chart. */
    public function enrollmentByClass(AcademicYear $year, int $ttl = 300): array
    {
        $key = "analytics:enrollment-by-class:{$year->id}:" . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () use ($year) {
            // SchoolClass carries BelongsToBranch, whose global scope already
            // filters to the active branch — no explicit BranchContext::apply needed here.
            $classes = \App\Models\SchoolClass::query()->orderBy('level')->get();

            return $classes->map(function ($class) use ($year) {
                $count = DB::table('student_section')
                    ->join('sections', 'sections.id', '=', 'student_section.section_id')
                    ->join('students', 'students.id', '=', 'student_section.student_id')
                    ->where('sections.school_class_id', $class->id)
                    ->where('student_section.academic_year_id', $year->id)
                    ->where('students.status', 'enrolled')
                    ->distinct()
                    ->count('student_section.student_id');

                return ['class_name' => $class->name, 'student_count' => $count];
            })->toArray();
        });
    }

    /** All-time total collected across every payment — the "Collected" half of the fee-collection pie. */
    public function totalCollectedAmount(int $ttl = 300): float
    {
        $key = 'analytics:total-collected-amount:' . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () {
            return (float) (BranchContext::apply(DB::table('payments'))->sum('amount') ?? 0);
        });
    }

    /**
     * "Arrivals Today" dashboard widget data: live counts + a recent-scans
     * feed. Deliberately NOT cached (unlike every other method here) — the
     * whole point is a live-feeling widget via short polling; caching this
     * would defeat that.
     */
    public function todayArrivalsFeed(int $recentLimit = 10): array
    {
        $today = today()->toDateString();

        $counts = BranchContext::apply(
            DB::table('attendances')->whereDate('date', $today)
        )->selectRaw("
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
            ")->first();

        $recent = BranchContext::apply(
            DB::table('attendances')
                ->join('students', 'students.id', '=', 'attendances.student_id')
                ->whereDate('attendances.date', $today)
                ->where('attendances.method', 'gate_scan'),
            'attendances.branch_id'
        )->select(
                'students.name_en', 'students.name_km', 'students.student_code',
                'attendances.status', 'attendances.arrival_time', 'attendances.departure_time'
            )
            ->orderByDesc('attendances.arrival_time')
            ->limit($recentLimit)
            ->get();

        return [
            'present' => (int) ($counts->present ?? 0),
            'late'    => (int) ($counts->late ?? 0),
            'absent'  => (int) ($counts->absent ?? 0),
            'recent'  => $recent,
        ];
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

    /** Monthly submission volume — same monthExpr/groupByRaw bucketing as attendanceByMonth/feeByMonth. */
    public function feedbackVolumeByMonth(int $ttl = 300): array
    {
        $key = 'analytics:feedback-volume-by-month:' . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () {
            $monthExpr = $this->monthExpr('created_at');

            return BranchContext::apply(DB::table('feedback_items'))
                ->selectRaw("{$monthExpr} as month, COUNT(*) as total")
                ->groupByRaw($monthExpr)
                ->orderBy('month')
                ->get()
                ->toArray();
        });
    }

    /** Average hours from submission to first resolution, across items that have ever been resolved. */
    public function feedbackAverageResolutionHours(int $ttl = 300): ?float
    {
        $key = 'analytics:feedback-avg-resolution-hours:' . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () {
            $diffExpr = $this->hoursDiffExpr('created_at', 'resolved_at');

            $avg = BranchContext::apply(
                DB::table('feedback_items')->whereNotNull('resolved_at')
            )->selectRaw("AVG({$diffExpr}) as avg_hours")->value('avg_hours');

            return $avg !== null ? round((float) $avg, 1) : null;
        });
    }

    /** Status breakdown per category — the satisfaction dashboard's main table. */
    public function feedbackCountsByCategory(int $ttl = 300): array
    {
        $key = 'analytics:feedback-counts-by-category:' . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () {
            return BranchContext::apply(DB::table('feedback_items'))
                ->selectRaw("
                    category,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
                ")
                ->groupBy('category')
                ->orderBy('category')
                ->get()
                ->toArray();
        });
    }

    /** % of published annual term results with result = 'pass'. Null when none published yet. */
    public function academicPassRate(AcademicYear $year, int $ttl = 300): ?float
    {
        $key = "analytics:academic-pass-rate:{$year->id}:" . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () use ($year) {
            $summary = BranchContext::apply(
                DB::table('term_results')
                    ->where('academic_year_id', $year->id)
                    ->where('is_published', true)
                    ->whereNull('semester')
            )->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN result = 'pass' THEN 1 ELSE 0 END) as passed
                ")->first();

            return $summary->total > 0 ? round($summary->passed / $summary->total * 100, 1) : null;
        });
    }

    /**
     * Average score/GPA per section, from published annual term results — one
     * row per section, highest average first (the view flags the first/last
     * row as the top/bottom performer, so this order is load-bearing).
     */
    public function academicAverageBySection(AcademicYear $year, int $ttl = 300): array
    {
        $key = "analytics:academic-avg-by-section:{$year->id}:" . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () use ($year) {
            return BranchContext::apply(
                DB::table('term_results')
                    ->join('sections', 'sections.id', '=', 'term_results.section_id')
                    ->join('school_classes', 'school_classes.id', '=', 'sections.school_class_id')
                    ->where('term_results.academic_year_id', $year->id)
                    ->where('term_results.is_published', true)
                    ->whereNull('term_results.semester'),
                'term_results.branch_id'
            )->select(
                    'school_classes.name as class_name', 'sections.name as section_name',
                    DB::raw('AVG(term_results.average) as avg_score'),
                    DB::raw('AVG(term_results.gpa) as avg_gpa'),
                    DB::raw('COUNT(*) as student_count')
                )
                ->groupBy('sections.id', 'school_classes.name', 'sections.name')
                ->orderByDesc('avg_score')
                ->get()
                ->toArray();
        });
    }

    /**
     * Average score per subject, from published exams only — one row per
     * subject, highest average first (same load-bearing order as above).
     */
    public function academicSubjectAverages(AcademicYear $year, int $ttl = 300): array
    {
        $key = "analytics:academic-subject-averages:{$year->id}:" . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () use ($year) {
            return BranchContext::apply(
                DB::table('exam_marks')
                    ->join('exams', 'exams.id', '=', 'exam_marks.exam_id')
                    ->join('subjects', 'subjects.id', '=', 'exam_marks.subject_id')
                    ->where('exams.academic_year_id', $year->id)
                    ->where('exams.is_published', true),
                'exam_marks.branch_id'
            )->select(
                    'subjects.name_en', 'subjects.name_km',
                    DB::raw('AVG(exam_marks.score) as avg_score'),
                    DB::raw('COUNT(*) as mark_count')
                )
                ->groupBy('subjects.id', 'subjects.name_en', 'subjects.name_km')
                ->orderByDesc('avg_score')
                ->get()
                ->toArray();
        });
    }

    /**
     * Count of exam marks per letter grade, from published exams only — grade
     * is computed here via a range join against grade_scales rather than
     * reading exam_marks.grade, which the mark-entry grid always leaves null.
     */
    public function academicGradeDistribution(AcademicYear $year, int $ttl = 300): array
    {
        $key = "analytics:academic-grade-distribution:{$year->id}:" . BranchContext::cacheKeySuffix();

        return Cache::remember($key, $ttl, function () use ($year) {
            return BranchContext::apply(
                DB::table('exam_marks')
                    ->join('exams', 'exams.id', '=', 'exam_marks.exam_id')
                    ->join('grade_scales', function ($join) {
                        $join->on('exam_marks.score', '>=', 'grade_scales.min_score')
                             ->on('exam_marks.score', '<=', 'grade_scales.max_score');
                    })
                    ->where('exams.academic_year_id', $year->id)
                    ->where('exams.is_published', true),
                'exam_marks.branch_id'
            )->select('grade_scales.grade', DB::raw('COUNT(*) as total'))
                ->groupBy('grade_scales.grade', 'grade_scales.min_score')
                ->orderByDesc('grade_scales.min_score')
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

    private function hoursDiffExpr(string $from, string $to): string
    {
        return DB::connection()->getDriverName() === 'mysql'
            ? "TIMESTAMPDIFF(HOUR, {$from}, {$to})"
            : "(julianday({$to}) - julianday({$from})) * 24";
    }
}
