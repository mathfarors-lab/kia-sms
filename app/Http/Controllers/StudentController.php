<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Services\StudentService;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function __construct(private StudentService $service) {}

    public function index(Request $request)
    {
        $this->authorize('students.view');

        $students = $this->filteredQuery($request)->latest()->paginate(20)->withQueryString();

        return view('students.index', compact('students'));
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('students.view');

        $students = $this->filteredQuery($request)->latest()->get();

        return Excel::download(new StudentsExport($students), 'students-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('students.view');

        $students = $this->filteredQuery($request)->latest()->get();

        $pdf = Pdf::loadView('pdf.students-list', compact('students'))
            ->setPaper('a4', 'landscape')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download('students-' . now()->format('Y-m-d') . '.pdf');
    }

    private function filteredQuery(Request $request)
    {
        $query = Student::query()->with('user');

        // students.view is shared broadly (accountant, receptionist, librarian
        // all legitimately need every student). Teachers are the one role that
        // must be scoped to their own homeroom + subject-taught sections —
        // without this, "Students" showed the entire school to every teacher.
        $user = auth()->user();
        if ($user->hasRole('teacher') && $user->staff) {
            $sectionIds = $user->staff->accessibleSectionIds();
            $activeYear = AcademicYear::where('is_active', true)->first();

            $query->whereHas('sections', function ($q) use ($sectionIds, $activeYear) {
                // wherePivot() doesn't compile correctly inside a whereHas
                // closure — it needs the pivot table's real column name here.
                $q->whereIn('sections.id', $sectionIds);
                if ($activeYear) {
                    $q->where('student_section.academic_year_id', $activeYear->id);
                }
            });
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_km', 'like', "%{$search}%")
                  ->orWhere('student_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        return $query;
    }

    public function create()
    {
        $this->authorize('students.create');
        return view('students.create');
    }

    public function store(StoreStudentRequest $request)
    {
        $student = $this->service->store(
            $request->safe()->except('photo'),
            $request->file('photo')
        );

        return redirect()->route('students.show', $student)
                         ->with('success', __('Student created successfully.'));
    }

    public function show(Student $student)
    {
        $this->authorize('students.view');
        $student->load('guardians', 'issuedDocuments', 'admissionApplication', 'documents');
        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $this->authorize('students.edit');
        return view('students.edit', compact('student'));
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $this->service->update(
            $student,
            $request->safe()->except('photo'),
            $request->file('photo')
        );

        return redirect()->route('students.show', $student)
                         ->with('success', __('Student updated successfully.'));
    }

    public function destroy(Student $student)
    {
        $this->authorize('students.delete');
        $this->service->destroy($student);
        return redirect()->route('students.index')
                         ->with('success', __('Student deleted.'));
    }
}
