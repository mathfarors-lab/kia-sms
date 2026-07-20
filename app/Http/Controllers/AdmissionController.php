<?php

namespace App\Http\Controllers;

use App\Exports\AdmissionsExport;
use App\Http\Requests\Admission\StoreAdmissionRequest;
use App\Http\Requests\Admission\UpdateAdmissionRequest;
use App\Models\AcademicYear;
use App\Models\AdmissionApplication;
use App\Models\SchoolClass;
use App\Services\AdmissionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AdmissionController extends Controller
{
    public function __construct(private AdmissionService $service) {}

    public function index(Request $request)
    {
        $this->authorize('admissions.view');

        $applications = $this->filteredQuery($request)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = AdmissionApplication::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admissions.index', compact('applications', 'counts'));
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('admissions.view');

        $applications = $this->filteredQuery($request)->latest()->get();

        return Excel::download(new AdmissionsExport($applications), 'admissions-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('admissions.view');

        $applications = $this->filteredQuery($request)->latest()->get();

        $pdf = Pdf::loadView('pdf.admissions-list', compact('applications'))
            ->setPaper('a4', 'landscape')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download('admissions-' . now()->format('Y-m-d') . '.pdf');
    }

    private function filteredQuery(Request $request)
    {
        return AdmissionApplication::with(['desiredClass', 'academicYear'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where(fn ($qq) => $qq
                ->where('name_en', 'like', "%{$request->search}%")
                ->orWhere('name_km', 'like', "%{$request->search}%")
                ->orWhere('application_no', 'like', "%{$request->search}%")));
    }

    public function create()
    {
        $this->authorize('admissions.manage');
        return view('admissions.create', $this->formOptions());
    }

    public function store(StoreAdmissionRequest $request)
    {
        $data = $request->safe()->except('document');
        $data['application_no'] = $this->service->generateNumber();

        if ($file = $request->file('document')) {
            $data['document_path']          = $file->store('admissions/documents', 'local');
            $data['document_original_name'] = $file->getClientOriginalName();
        }

        $application = AdmissionApplication::create($data);

        return redirect()->route('admissions.show', $application)
            ->with('success', __('admissions.created'));
    }

    public function show(AdmissionApplication $admission)
    {
        $this->authorize('admissions.view');
        $admission->load(['desiredClass', 'academicYear', 'reviewer', 'student']);
        return view('admissions.show', ['application' => $admission]);
    }

    public function edit(AdmissionApplication $admission)
    {
        $this->authorize('admissions.manage');

        if ($admission->isConverted()) {
            return redirect()->route('admissions.show', $admission)
                ->with('error', __('admissions.converted_locked'));
        }

        return view('admissions.edit', ['application' => $admission] + $this->formOptions());
    }

    public function update(UpdateAdmissionRequest $request, AdmissionApplication $admission)
    {
        if ($admission->isConverted()) {
            return redirect()->route('admissions.show', $admission)
                ->with('error', __('admissions.converted_locked'));
        }

        $data = $request->safe()->except('document');

        if ($file = $request->file('document')) {
            if ($admission->document_path) {
                Storage::disk('local')->delete($admission->document_path);
            }
            $data['document_path']          = $file->store('admissions/documents', 'local');
            $data['document_original_name'] = $file->getClientOriginalName();
        }

        $admission->update($data);

        return redirect()->route('admissions.show', $admission)
            ->with('success', __('admissions.updated'));
    }

    /** Move the application along the pipeline (never out of converted). */
    public function updateStatus(Request $request, AdmissionApplication $admission)
    {
        $this->authorize('admissions.manage');

        $data = $request->validate(['status' => 'required|in:under_review,accepted,rejected']);

        if ($admission->isConverted()) {
            return back()->with('error', __('admissions.converted_locked'));
        }

        $admission->update([
            'status'      => $data['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', __('admissions.status_updated'));
    }

    /** Accepted → enrolled Student (idempotent). */
    public function convert(Request $request, AdmissionApplication $admission)
    {
        $this->authorize('admissions.manage');

        $student = $this->service->convertToStudent($admission, $request->user()->id);

        return redirect()->route('students.show', $student)
            ->with('success', __('admissions.converted', ['code' => $student->student_code]));
    }

    /** Gated download of the supporting document (private disk). */
    public function document(AdmissionApplication $admission)
    {
        $this->authorize('admissions.view');

        abort_unless($admission->document_path && Storage::disk('local')->exists($admission->document_path), 404);

        return response()->download(
            Storage::disk('local')->path($admission->document_path),
            $admission->document_original_name ?? 'document'
        );
    }

    private function formOptions(): array
    {
        return [
            'classes' => SchoolClass::orderBy('name')->get(),
            'years'   => AcademicYear::orderByDesc('start_date')->get(),
        ];
    }
}
