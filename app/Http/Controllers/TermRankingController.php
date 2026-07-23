<?php

namespace App\Http\Controllers;

use App\Exports\TermRankingExport;
use App\Models\AcademicYear;
use App\Models\TermResult;
use App\Support\Permissions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class TermRankingController extends Controller
{
    public function index()
    {
        $this->authorize(Permissions::TERM_RESULTS_MANAGE);

        $publishedPeriods = TermResult::where('is_published', true)
            ->select('academic_year_id', 'semester')
            ->distinct()
            ->with('academicYear')
            ->get()
            ->sortBy([['academic_year_id', 'desc'], ['semester', 'asc']])
            ->groupBy(fn ($tr) => $tr->academicYear->name ?? 'Unknown');

        return view('term-ranking.index', compact('publishedPeriods'));
    }

    public function show(AcademicYear $academicYear, string $semesterSlug)
    {
        $this->authorize(Permissions::TERM_RESULTS_MANAGE);

        $semester = $semesterSlug === 'annual' ? null : (int) $semesterSlug;
        $ranking = $this->buildTermRanking($academicYear, $semester);
        $stats = $this->computeStats($ranking);
        $classes = $ranking->pluck('class_name')->unique()->filter()->sort()->values();
        $filterClass = request('class');

        $displayed = $filterClass
            ? $ranking->where('class_name', $filterClass)->values()
            : $ranking;

        return view('term-ranking.show', [
            'title' => $academicYear->name.' — '.$this->periodLabel($semesterSlug),
            'academicYear' => $academicYear,
            'semesterSlug' => $semesterSlug,
            'ranking' => $displayed,
            'stats' => $stats,
            'classes' => $classes,
            'filterClass' => $filterClass,
        ]);
    }

    public function excel(AcademicYear $academicYear, string $semesterSlug)
    {
        $this->authorize(Permissions::TERM_RESULTS_MANAGE);

        $semester = $semesterSlug === 'annual' ? null : (int) $semesterSlug;
        $ranking = $this->buildTermRanking($academicYear, $semester);
        $title = $academicYear->name.' ('.$this->periodLabel($semesterSlug).')';

        return Excel::download(
            new TermRankingExport($ranking, $title),
            'term-ranking-'.str($academicYear->name.'-'.$semesterSlug)->slug().'.xlsx'
        );
    }

    public function pdf(AcademicYear $academicYear, string $semesterSlug)
    {
        $this->authorize(Permissions::TERM_RESULTS_MANAGE);

        $semester = $semesterSlug === 'annual' ? null : (int) $semesterSlug;
        $ranking = $this->buildTermRanking($academicYear, $semester);
        $title = $academicYear->name.' — '.$this->periodLabel($semesterSlug);
        $stats = $this->computeStats($ranking);
        $classes = $ranking->pluck('class_name')->unique()->filter()->sort()->values();

        $pdf = Pdf::loadView('pdf.term-ranking', compact('ranking', 'title', 'stats', 'classes'))
            ->setPaper('a4', 'landscape')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download('term-ranking-'.str($academicYear->name.'-'.$semesterSlug)->slug().'.pdf');
    }

    private function periodLabel(string $semesterSlug): string
    {
        return $semesterSlug === 'annual'
            ? __('academic_ranking.annual')
            : __('academic_ranking.semester_n', ['n' => $semesterSlug]);
    }

    /**
     * term_results.rank (see TermGradingService::assignRanks()) is computed
     * separately inside a per-section loop — it only ever ranks a student
     * against their own section, never across the whole grade or school.
     * Both ranks here are computed fresh, same standard-competition tie
     * rule, mirroring SchoolRankingController::buildExamRanking() — the
     * only difference is school_rank isn't already sitting on a column
     * here, so it's computed the same way class_rank is there.
     */
    private function buildTermRanking(AcademicYear $academicYear, ?int $semester): Collection
    {
        $yearId = $academicYear->id;

        $rows = TermResult::where('term_results.academic_year_id', $yearId)
            ->where('term_results.semester', $semester)
            ->where('term_results.is_published', true)
            ->join('students', 'term_results.student_id', '=', 'students.id')
            ->leftJoin('student_section', function ($join) use ($yearId) {
                $join->on('students.id', '=', 'student_section.student_id')
                    ->where('student_section.academic_year_id', '=', $yearId);
            })
            ->leftJoin('sections', 'student_section.section_id', '=', 'sections.id')
            ->leftJoin('school_classes', 'sections.school_class_id', '=', 'school_classes.id')
            ->select([
                'term_results.total',
                'term_results.average',
                'term_results.gpa',
                'term_results.result',
                'students.id as student_id',
                'students.student_code',
                'students.name_en',
                'students.name_km',
                'sections.name as section_name',
                'school_classes.name as class_name',
                'school_classes.level as class_level',
                'student_section.roll_no',
            ])
            ->orderByDesc('term_results.average')
            ->orderBy('students.name_en')
            ->get();

        // School-wide rank, standard competition ranking (ties share a rank).
        $rows = $rows->values();
        $schoolRank = 1;
        $prevAverage = null;
        foreach ($rows as $i => $row) {
            $row->school_rank = ($i > 0 && (float) $row->average === (float) $prevAverage)
                ? $rows[$i - 1]->school_rank
                : $schoolRank;
            $prevAverage = $row->average;
            $schoolRank++;
        }

        // Grade-level rank — identical approach to buildExamRanking().
        $classPosition = [];
        $classLastRank = [];
        $classRankMap = [];
        $prevAvgByClass = [];

        $forClassRank = $rows->sortBy([
            ['class_level', 'asc'],
            ['class_name', 'asc'],
            ['school_rank', 'asc'],
        ])->values();

        foreach ($forClassRank as $row) {
            $cls = $row->class_name ?? '—';
            if (! isset($classPosition[$cls])) {
                $classPosition[$cls] = 1;
                $prevAvgByClass[$cls] = null;
                $classLastRank[$cls] = null;
            }

            $tied = $prevAvgByClass[$cls] !== null && (float) $row->average === (float) $prevAvgByClass[$cls];
            $rank = $tied ? $classLastRank[$cls] : $classPosition[$cls];

            $classRankMap[$cls][$row->student_id] = $rank;
            $classLastRank[$cls] = $rank;
            $prevAvgByClass[$cls] = $row->average;
            $classPosition[$cls]++;
        }

        return $rows->map(function ($row) use ($classRankMap) {
            $cls = $row->class_name ?? '—';
            $row->class_rank = $classRankMap[$cls][$row->student_id] ?? '—';

            return $row;
        });
    }

    private function computeStats(Collection $ranking): array
    {
        if ($ranking->isEmpty()) {
            return ['total' => 0, 'pass' => 0, 'fail' => 0, 'pass_rate' => 0, 'average' => 0, 'top' => null];
        }

        $passed = $ranking->where('result', 'pass')->count();
        $total = $ranking->count();

        return [
            'total' => $total,
            'pass' => $passed,
            'fail' => $total - $passed,
            'pass_rate' => $total > 0 ? round($passed / $total * 100, 1) : 0,
            'average' => round($ranking->avg('average'), 2),
            'top' => $ranking->where('school_rank', 1)->first(),
        ];
    }
}
