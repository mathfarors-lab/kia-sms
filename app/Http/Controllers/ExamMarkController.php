<?php

namespace App\Http\Controllers;

use App\Exports\ExamMarksExport;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Support\Permissions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ExamMarkController extends Controller
{
    public function index()
    {
        $this->authorize('marks.view');

        $user = auth()->user();
        $staff = $user->staff;
        $isAdmin = $user->hasPermissionTo(Permissions::EXAMS_MANAGE);

        $activeYear = AcademicYear::where('is_active', true)->first();

        $exams = Exam::with('academicYear')
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->orderByRaw("FIELD(type, 'monthly', 'midterm', 'final')")
            ->orderBy('exam_date')
            ->get();

        // Determine which sections the current user can enter marks for
        $teacherSectionIds = $isAdmin ? null : $this->allowedSectionIds($staff);

        $sections = Section::with('schoolClass')
            ->when($teacherSectionIds !== null, fn ($q) => $q->whereIn('id', $teacherSectionIds))
            ->orderBy('school_class_id')
            ->get();

        // Collect student IDs per section (filtered to active year). Calling
        // wherePivot() directly (not inside ->when()'s closure) matters here:
        // when() forwards its callback the raw underlying query builder, which
        // has no pivot awareness — wherePivot() there silently misparses as a
        // dynamic where("pivot", ...) instead of scoping to the pivot table.
        $sectionStudentIds = [];
        foreach ($sections as $section) {
            $query = $section->students();
            if ($activeYear) {
                $query->wherePivot('academic_year_id', $activeYear->id);
            }
            $sectionStudentIds[$section->id] = $query->pluck('students.id')->toArray();
        }

        // Subject count per class (for "expected" total)
        $subjectCountByClass = [];
        foreach ($sections as $section) {
            $clsId = $section->school_class_id;
            if (! isset($subjectCountByClass[$clsId])) {
                $subjectCountByClass[$clsId] = $section->schoolClass->subjects()->count();
            }
        }

        // Bulk-load mark counts: exam_id → student_id → count
        $allStudentIds = collect($sectionStudentIds)->flatten()->unique();
        $examIds = $exams->pluck('id');

        $markCounts = ExamMark::whereIn('exam_id', $examIds)
            ->whereIn('student_id', $allStudentIds)
            ->selectRaw('exam_id, student_id, COUNT(*) as cnt')
            ->groupBy('exam_id', 'student_id')
            ->get()
            ->groupBy('exam_id')
            ->map(fn ($rows) => $rows->keyBy('student_id'));

        // Build completion matrix[exam_id][section_id]
        $completion = [];
        foreach ($exams as $exam) {
            foreach ($sections as $section) {
                $studentIds = $sectionStudentIds[$section->id];
                $studentCount = count($studentIds);
                if ($studentCount === 0) {
                    continue;
                }

                $subjectCount = $subjectCountByClass[$section->school_class_id] ?? 0;
                $expected = $studentCount * $subjectCount;

                $entered = 0;
                $rows = $markCounts->get($exam->id, collect());
                foreach ($studentIds as $sid) {
                    $entered += (int) ($rows->get($sid)?->cnt ?? 0);
                }

                $completion[$exam->id][$section->id] = [
                    'entered' => $entered,
                    'expected' => $expected,
                    'status' => match (true) {
                        $subjectCount === 0 => 'no-subjects',
                        $entered === 0 => 'empty',
                        $entered >= $expected => 'complete',
                        default => 'partial',
                    },
                    'students' => $studentCount,
                ];
            }
        }

        $monthlyExams = $exams->where('type', 'monthly')->values();
        $otherExams = $exams->whereIn('type', ['midterm', 'final'])->values();

        return view('exam-marks.index', compact(
            'exams', 'sections', 'completion', 'activeYear',
            'isAdmin', 'monthlyExams', 'otherExams'
        ));
    }

    public function grid(Exam $exam, Section $section)
    {
        $this->authorize('marks.entry');
        $this->authorizeSectionAccess($section);

        [$subjects, $students, $marks] = $this->gridData($exam, $section);

        return view('exam-marks.grid', compact('exam', 'section', 'subjects', 'students', 'marks'));
    }

    public function exportExcel(Exam $exam, Section $section)
    {
        $this->authorize('marks.entry');
        $this->authorizeSectionAccess($section);

        [$subjects, $students, $marks] = $this->gridData($exam, $section);

        return Excel::download(
            new ExamMarksExport($students, $subjects, $marks),
            'exam-marks-'.$exam->id.'-'.$section->id.'.xlsx'
        );
    }

    public function exportPdf(Exam $exam, Section $section)
    {
        $this->authorize('marks.entry');
        $this->authorizeSectionAccess($section);

        [$subjects, $students, $marks] = $this->gridData($exam, $section);

        $pdf = Pdf::loadView('pdf.exam-marks-grid', compact('exam', 'section', 'subjects', 'students', 'marks'))
            ->setPaper('a4', 'landscape')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download('exam-marks-'.$exam->id.'-'.$section->id.'.pdf');
    }

    private function gridData(Exam $exam, Section $section): array
    {
        $subjects = $section->schoolClass->subjects()->orderBy('name_en')->get();
        $students = $section->students()->orderBy('name_en')->get();

        $marks = ExamMark::where('exam_id', $exam->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->keyBy('subject_id'));

        return [$subjects, $students, $marks];
    }

    /** Section IDs a teacher may enter marks for: their homeroom + any class they teach a subject in. */
    private function allowedSectionIds(?Staff $staff): Collection
    {
        return $staff?->accessibleSectionIds() ?? collect();
    }

    /**
     * marks.entry only proves a user may enter marks SOMEWHERE — it does not
     * scope WHICH section. Without this, any teacher could edit any other
     * teacher's section by guessing the URL, since grid()/save()/exports all
     * take a raw {section} route param. Admins (exams.manage) bypass this,
     * same split used in index().
     */
    private function authorizeSectionAccess(Section $section): void
    {
        $user = auth()->user();

        if ($user->hasPermissionTo(Permissions::EXAMS_MANAGE)) {
            return;
        }

        if (! $this->allowedSectionIds($user->staff)->contains($section->id)) {
            abort(403);
        }
    }

    public function save(Request $request, Exam $exam, Section $section)
    {
        $this->authorize('marks.entry');
        $this->authorizeSectionAccess($section);

        if ($exam->is_published) {
            return back()->withErrors(['exam' => __('exam.locked_published')]);
        }

        $request->validate(['marks' => 'required|array']);

        // Per-subject max, not a flat 100 — the grid's HTML input already caps
        // each field at the subject's own full_mark, but the server-side rule
        // didn't match it, so a full_mark=50 subject could silently accept a
        // score up to 100 if the HTML cap were ever bypassed.
        $fullMarks = $section->schoolClass->subjects()
            ->get(['subjects.id', 'subjects.full_mark'])
            ->pluck('full_mark', 'id');

        $rules = [];
        foreach ($request->marks as $studentId => $subjectScores) {
            foreach ($subjectScores as $subjectId => $score) {
                $max = $fullMarks[$subjectId] ?? 100;
                $rules["marks.{$studentId}.{$subjectId}"] = "nullable|numeric|min:0|max:{$max}";
            }
        }
        $request->validate($rules);

        foreach ($request->marks as $studentId => $subjectScores) {
            foreach ($subjectScores as $subjectId => $score) {
                if ($score === null || $score === '') {
                    ExamMark::where([
                        'exam_id' => $exam->id, 'student_id' => $studentId, 'subject_id' => $subjectId,
                    ])->delete();

                    continue;
                }

                ExamMark::updateOrCreate(
                    ['exam_id' => $exam->id, 'student_id' => $studentId, 'subject_id' => $subjectId],
                    ['score' => $score, 'grade' => null]
                );
            }
        }

        return back()->with('success', __('messages.saved'));
    }
}
