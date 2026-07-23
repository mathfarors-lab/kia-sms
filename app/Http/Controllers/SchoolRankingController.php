<?php

namespace App\Http\Controllers;

use App\Exports\SchoolRankingExport;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Support\Permissions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class SchoolRankingController extends Controller
{
    public function index()
    {
        $this->authorize(Permissions::TERM_RESULTS_MANAGE);

        $years = AcademicYear::latest()->get();

        $publishedExams = Exam::with('academicYear')
            ->where('is_published', true)
            ->latest('exam_date')
            ->get()
            ->groupBy(fn ($e) => $e->academicYear->name ?? 'Unknown');

        return view('school-ranking.index', compact('years', 'publishedExams'));
    }

    public function examRanking(Exam $exam)
    {
        $this->authorize(Permissions::TERM_RESULTS_MANAGE);

        $ranking = $this->buildExamRanking($exam);
        $stats = $this->computeStats($ranking);
        $classes = $ranking->pluck('class_name')->unique()->filter()->sort()->values();
        $filterClass = request('class');

        $displayed = $filterClass
            ? $ranking->where('class_name', $filterClass)->values()
            : $ranking;

        return view('school-ranking.show', [
            'title' => $exam->name.' — '.($exam->academicYear->name ?? ''),
            'exam' => $exam,
            'ranking' => $displayed,
            'stats' => $stats,
            'classes' => $classes,
            'filterClass' => $filterClass,
            'type' => 'exam',
        ]);
    }

    public function examRankingExcel(Exam $exam)
    {
        $this->authorize(Permissions::TERM_RESULTS_MANAGE);

        $ranking = $this->buildExamRanking($exam);
        $title = $exam->name.' ('.($exam->academicYear->name ?? '').')';

        return Excel::download(
            new SchoolRankingExport($ranking, $title),
            'school-ranking-'.str($exam->name)->slug().'.xlsx'
        );
    }

    public function examRankingPdf(Exam $exam)
    {
        $this->authorize(Permissions::TERM_RESULTS_MANAGE);

        $ranking = $this->buildExamRanking($exam);
        $title = $exam->name.' — '.($exam->academicYear->name ?? '');
        $stats = $this->computeStats($ranking);
        $classes = $ranking->pluck('class_name')->unique()->filter()->sort()->values();

        $pdf = Pdf::loadView('pdf.school-ranking', compact('ranking', 'title', 'exam', 'stats', 'classes'))
            ->setPaper('a4', 'landscape')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download('school-ranking-'.str($exam->name)->slug().'.pdf');
    }

    private function buildExamRanking(Exam $exam): Collection
    {
        $yearId = $exam->academic_year_id;

        $rows = ExamResult::where('exam_results.exam_id', $exam->id)
            ->join('students', 'exam_results.student_id', '=', 'students.id')
            ->leftJoin('student_section', function ($join) use ($yearId) {
                $join->on('students.id', '=', 'student_section.student_id')
                    ->where('student_section.academic_year_id', '=', $yearId);
            })
            ->leftJoin('sections', 'student_section.section_id', '=', 'sections.id')
            ->leftJoin('school_classes', 'sections.school_class_id', '=', 'school_classes.id')
            ->select([
                'exam_results.rank as school_rank',
                'exam_results.total',
                'exam_results.average',
                'exam_results.gpa',
                'exam_results.result',
                'students.id as student_id',
                'students.student_code',
                'students.name_en',
                'students.name_km',
                'sections.name as section_name',
                'school_classes.name as class_name',
                'school_classes.level as class_level',
                'student_section.roll_no',
            ])
            ->orderBy('exam_results.rank')
            ->orderBy('students.name_en')
            ->get();

        // Compute per-class rank from school rank order (standard competition
        // ranking, same tie-share rule as GradingService::computeResults —
        // a rank must propagate across an entire tie chain, e.g. 1,1,1,4).
        $classPosition = []; // 1-based position within the class, increments every row
        $classLastRank = []; // rank assigned to the previous row in this class
        $classRankMap = [];
        $prevAvgByClass = [];

        // Sort by class then school_rank to assign class rank sequentially
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
