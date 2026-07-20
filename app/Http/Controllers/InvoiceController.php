<?php

namespace App\Http\Controllers;

use App\Exports\InvoicesListExport;
use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Services\InvoiceService;
use App\Services\KhqrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private KhqrService    $khqr,
    ) {}

    public function index(Request $request)
    {
        $invoices = $this->filteredQuery($request)->paginate(25)->withQueryString();
        return view('invoices.index', compact('invoices'));
    }

    public function exportExcel(Request $request)
    {
        $invoices = $this->filteredQuery($request)->get();

        return Excel::download(new InvoicesListExport($invoices), 'invoices-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $invoices = $this->filteredQuery($request)->get();

        $pdf = Pdf::loadView('pdf.invoices-list', compact('invoices'))
            ->setPaper('a4', 'landscape')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download('invoices-' . now()->format('Y-m-d') . '.pdf');
    }

    private function filteredQuery(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole(['admin', 'accountant', 'principal'])) {
            $this->authorize('invoices.view');
            return Invoice::with(['student', 'academicYear'])
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->when($request->search, fn ($q) => $q->where('number', 'like', '%' . $request->search . '%'))
                ->latest();
        }

        if ($user->hasRole(['parent', 'student'])) {
            $studentId = $user->hasRole('student')
                ? $user->student?->id
                : $user->wards()->pluck('students.id');

            return Invoice::with(['student', 'academicYear'])
                ->when(is_array($studentId) || $studentId instanceof \Illuminate\Support\Collection
                    ? true : false,
                    fn ($q) => $q->whereIn('student_id', $studentId),
                    fn ($q) => $q->where('student_id', $studentId),
                )->latest();
        }

        abort(403);
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeInvoiceAccess($invoice);
        $invoice->load(['student', 'academicYear', 'items', 'payments.receivedBy']);

        $paymentIntent = null;
        if (!$invoice->isPaid()) {
            $paymentIntent = $this->khqr->getOrCreateIntent($invoice);
        }

        return view('invoices.show', compact('invoice', 'paymentIntent'));
    }

    public function create()
    {
        $this->authorize('invoices.create');
        $classes       = SchoolClass::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        return view('invoices.generate', compact('classes', 'academicYears'));
    }

    public function generate(Request $request)
    {
        $this->authorize('invoices.create');
        $data = $request->validate([
            'school_class_id'  => 'required|exists:school_classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term'             => 'required|string|max:50',
            'due_date'         => 'nullable|date|after:today',
        ]);

        $class = SchoolClass::findOrFail($data['school_class_id']);
        $year  = AcademicYear::findOrFail($data['academic_year_id']);

        $result = $this->invoiceService->generateForClass(
            $class, $year, $data['term'],
            isset($data['due_date']) ? \Carbon\Carbon::parse($data['due_date']) : null
        );

        return redirect()->route('invoices.index')
            ->with('success', __('invoice.generated', [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
            ]));
    }

    public function regenerateKhqr(Invoice $invoice)
    {
        $this->authorizeInvoiceAccess($invoice);

        if ($invoice->isPaid()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('info', 'Invoice is already paid.');
        }

        // Expire the current pending intent so getOrCreateIntent() creates a fresh one.
        \App\Models\PaymentIntent::where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        // Create a new intent (new QR, new MD5, new expiry window).
        $this->khqr->getOrCreateIntent($invoice);

        return redirect()->route('invoices.show', $invoice);
    }

    private function authorizeInvoiceAccess(Invoice $invoice): void
    {
        $user = Auth::user();

        if ($user->hasRole(['admin', 'accountant', 'principal'])) {
            return;
        }

        if ($user->hasRole('student')) {
            abort_unless($user->student?->id === $invoice->student_id, 403);
            return;
        }

        if ($user->hasRole('parent')) {
            abort_unless($user->wards()->pluck('students.id')->contains($invoice->student_id), 403);
            return;
        }

        abort(403);
    }
}
