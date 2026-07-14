<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ReportComment;
use App\Models\Section;
use App\Models\Student;
use App\Models\TermResult;
use App\Services\TermGradingService;
use App\Support\Permissions as P;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TermResultController extends Controller
{
    public function __construct(private TermGradingService $service) {}

    /** Admin/principal: select year + semester, see computed results per section. */
    public function index(Request $request)
    {
        $this->authorize(P::EXAMS_VIEW);

        $years           = AcademicYear::orderByDesc('start_date')->get();
        $selectedYear    = $request->year
            ? AcademicYear::findOrFail($request->year)
            : AcademicYear::where('is_active', true)->first();
        $selectedSemester = $request->filled('semester') ? (int) $request->semester : 1;

        // Resolve "annual" slug to null
        if ($request->semester === 'annual') {
            $selectedSemester = null;
        }

        $termResults     = collect();
        $sectionIds      = [];

        if ($selectedYear) {
            $dbSemester  = $selectedSemester; // null = annual
            $termResults = TermResult::where('academic_year_id', $selectedYear->id)
                ->where('semester', $dbSemester)
                ->with(['student', 'section.schoolClass'])
                ->orderBy('rank')
                ->get()
                ->groupBy('section_id');

            $sectionIds = DB::table('student_section')
                ->where('academic_year_id', $selectedYear->id)
                ->distinct()
                ->pluck('section_id');
        }

        $sections = Section::whereIn('id', $sectionIds)->with('schoolClass')->get();

        return view('term-results.index', compact(
            'years', 'selectedYear', 'selectedSemester', 'sections', 'termResults'
        ));
    }

    /** Trigger (re)computation for a year + semester. */
    public function compute(Request $request)
    {
        $this->authorize(P::TERM_RESULTS_MANAGE);

        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester'         => 'required|in:1,2,annual',
        ]);

        $year     = AcademicYear::findOrFail($data['academic_year_id']);
        $semester = $data['semester'] === 'annual' ? null : (int) $data['semester'];

        $ok = $this->service->compute($year, $semester);

        return $ok
            ? back()->with('success', __('term_results.computed'))
            : back()->with('error', __('term_results.finalized_cannot_recompute'));
    }

    /** Lock (finalize) results — no further recomputation allowed. */
    public function finalize(Request $request, AcademicYear $academicYear)
    {
        $this->authorize(P::TERM_RESULTS_MANAGE);

        $data     = $request->validate(['semester' => 'required|in:1,2,annual']);
        $semester = $data['semester'] === 'annual' ? null : (int) $data['semester'];

        TermResult::where('academic_year_id', $academicYear->id)
            ->where('semester', $semester)
            ->update(['is_finalized' => true]);

        activity()->log("Finalized term results: year={$academicYear->name} semester=" . ($semester ?? 'annual'));

        return back()->with('success', __('term_results.finalized'));
    }

    /** Publish results — students/parents can now see them. */
    public function publish(Request $request, AcademicYear $academicYear)
    {
        $this->authorize(P::TERM_RESULTS_PUBLISH);

        $data     = $request->validate(['semester' => 'required|in:1,2,annual']);
        $semester = $data['semester'] === 'annual' ? null : (int) $data['semester'];

        TermResult::where('academic_year_id', $academicYear->id)
            ->where('semester', $semester)
            ->update(['is_published' => true]);

        return back()->with('success', __('term_results.published'));
    }

    /** Per-student consolidated term/annual report (HTML). */
    public function show(AcademicYear $academicYear, string $semesterSlug, Student $student)
    {
        $this->authorizeView($student);

        $semester   = $semesterSlug === 'annual' ? null : (int) $semesterSlug;
        $termResult = TermResult::where([
            'academic_year_id' => $academicYear->id,
            'semester'         => $semester,
            'student_id'       => $student->id,
        ])->firstOrFail();

        if (!Auth::user()->hasRole(['admin', 'principal', 'teacher'])) {
            abort_unless($termResult->is_published, 403, __('term_results.not_published'));
        }

        $exams = $this->componentExams($academicYear, $semester, $student->id);

        return view('term-results.show', compact(
            'academicYear', 'semesterSlug', 'semester', 'student', 'termResult', 'exams'
        ));
    }

    /** Per-student consolidated PDF download. */
    public function pdf(AcademicYear $academicYear, string $semesterSlug, Student $student)
    {
        $this->authorizeView($student);

        $semester   = $semesterSlug === 'annual' ? null : (int) $semesterSlug;
        $termResult = TermResult::where([
            'academic_year_id' => $academicYear->id,
            'semester'         => $semester,
            'student_id'       => $student->id,
        ])->firstOrFail();

        if (!Auth::user()->hasRole(['admin', 'principal', 'teacher'])) {
            abort_unless($termResult->is_published, 403, __('term_results.not_published'));
        }

        $exams = $this->componentExams($academicYear, $semester, $student->id);

        $pdf = Pdf::loadView('pdf.term-report-card', compact(
            'academicYear', 'semesterSlug', 'semester', 'student', 'termResult', 'exams'
        ))->setPaper('a4')->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        $label    = $semesterSlug === 'annual' ? 'annual' : "sem{$semester}";
        $filename = "term-report-{$student->student_code}-{$academicYear->id}-{$label}.pdf";

        return $pdf->download($filename);
    }

    /** Edit form for a single student's teacher_remark, with a pick-from-bank shortcut. */
    public function editRemark(AcademicYear $academicYear, string $semesterSlug, Student $student)
    {
        $this->authorize(P::TERM_RESULTS_MANAGE);

        $semester   = $semesterSlug === 'annual' ? null : (int) $semesterSlug;
        $termResult = TermResult::where([
            'academic_year_id' => $academicYear->id,
            'semester'         => $semester,
            'student_id'       => $student->id,
        ])->firstOrFail();

        $comments = ReportComment::orderBy('category')->orderBy('id')->get();

        return view('term-results.edit-remark', compact(
            'academicYear', 'semesterSlug', 'student', 'termResult', 'comments'
        ));
    }

    public function updateRemark(Request $request, AcademicYear $academicYear, string $semesterSlug, Student $student)
    {
        $this->authorize(P::TERM_RESULTS_MANAGE);

        $data = $request->validate(['teacher_remark' => ['nullable', 'string', 'max:2000']]);

        $semester = $semesterSlug === 'annual' ? null : (int) $semesterSlug;
        TermResult::where([
            'academic_year_id' => $academicYear->id,
            'semester'         => $semester,
            'student_id'       => $student->id,
        ])->firstOrFail()->update(['teacher_remark' => $data['teacher_remark']]);

        return redirect()
            ->route('term-results.show', [$academicYear, $semesterSlug, $student])
            ->with('success', __('term_results.remark_saved'));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private function componentExams(AcademicYear $academicYear, ?int $semester, int $studentId)
    {
        return Exam::published()
            ->where('academic_year_id', $academicYear->id)
            ->when($semester !== null, fn ($q) => $q->where('semester', $semester))
            ->when($semester === null, fn ($q) => $q->whereIn('semester', [1, 2]))
            ->with(['marks' => fn ($q) => $q->where('student_id', $studentId)->with('subject')])
            ->orderBy('semester')
            ->orderBy('id')
            ->get();
    }

    private function authorizeView(Student $student): void
    {
        $user = Auth::user();
        if ($user->hasRole(['admin', 'principal', 'teacher'])) {
            return;
        }
        if ($user->hasRole('student')) {
            $own = Student::where('user_id', $user->id)->value('id');
            abort_unless($own === $student->id, 403);
            return;
        }
        if ($user->hasRole('parent')) {
            abort_unless($user->wards()->where('students.id', $student->id)->exists(), 403);
            return;
        }
        abort(403);
    }
}
