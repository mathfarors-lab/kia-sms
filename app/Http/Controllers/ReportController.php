<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Section;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function index()
    {
        Gate::authorize(Permissions::REPORTS_VIEW);
        $years = AcademicYear::orderByDesc('start_date')->get();
        return view('reports.index', compact('years'));
    }

    public function enrollmentRoster(Request $request)
    {
        Gate::authorize(Permissions::REPORTS_VIEW);

        $request->validate(['year_id' => 'required|exists:academic_years,id']);
        $year = AcademicYear::findOrFail($request->year_id);

        $students = DB::table('student_section')
            ->join('students', 'students.id', '=', 'student_section.student_id')
            ->join('sections', 'sections.id', '=', 'student_section.section_id')
            ->join('school_classes', 'school_classes.id', '=', 'sections.school_class_id')
            ->where('student_section.academic_year_id', $year->id)
            ->select(
                'students.student_code',
                'students.name_en',
                'students.name_km',
                'students.gender',
                'students.dob',
                'school_classes.name as class_name',
                'sections.name as section_name',
                'student_section.roll_no'
            )
            ->orderBy('school_classes.name')
            ->orderBy('sections.name')
            ->orderBy('student_section.roll_no')
            ->get();

        if ($request->format === 'pdf') {
            return $this->pdfResponse('reports.pdf.enrollment', compact('year', 'students'), "enrollment-{$year->name}.pdf");
        }

        if ($request->format === 'excel') {
            return $this->excelResponse($students, "enrollment-{$year->name}.xlsx", [
                'Student Code', 'Name (EN)', 'Name (KM)', 'Gender', 'DOB', 'Class', 'Section', 'Roll No',
            ], ['student_code', 'name_en', 'name_km', 'gender', 'dob', 'class_name', 'section_name', 'roll_no']);
        }

        return view('reports.enrollment', compact('year', 'students'));
    }

    public function attendanceSummary(Request $request)
    {
        Gate::authorize(Permissions::REPORTS_VIEW);

        $request->validate([
            'year_id'    => 'required|exists:academic_years,id',
            'from'       => 'nullable|date',
            'to'         => 'nullable|date|after_or_equal:from',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $year = AcademicYear::findOrFail($request->year_id);

        $query = DB::table('student_section')
            ->join('students', 'students.id', '=', 'student_section.student_id')
            ->join('sections', 'sections.id', '=', 'student_section.section_id')
            ->join('school_classes', 'school_classes.id', '=', 'sections.school_class_id')
            ->join('attendances', function ($j) {
                $j->on('attendances.student_id', '=', 'student_section.student_id')
                  ->on('attendances.section_id', '=', 'student_section.section_id');
            })
            ->where('student_section.academic_year_id', $year->id)
            ->when($request->section_id, fn($q) => $q->where('sections.id', $request->section_id))
            ->when($request->from, fn($q) => $q->where('attendances.date', '>=', $request->from))
            ->when($request->to, fn($q) => $q->where('attendances.date', '<=', $request->to))
            ->select(
                'students.student_code',
                'students.name_en',
                'school_classes.name as class_name',
                'sections.name as section_name',
                DB::raw("COUNT(*) as total_days"),
                DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as late")
            )
            ->groupBy('students.id', 'students.student_code', 'students.name_en', 'school_classes.name', 'sections.name')
            ->orderBy('school_classes.name')
            ->orderBy('sections.name')
            ->get()
            ->map(function ($row) {
                $row->rate = $row->total_days > 0
                    ? round($row->present / $row->total_days * 100, 1)
                    : 0;
                return $row;
            });

        $sections = Section::with('schoolClass')->get();

        if ($request->format === 'pdf') {
            return $this->pdfResponse('reports.pdf.attendance', compact('year', 'query'), "attendance-{$year->name}.pdf");
        }

        $rows = $query;
        return view('reports.attendance', compact('year', 'rows', 'sections'));
    }

    public function feeCollection(Request $request)
    {
        Gate::authorize(Permissions::REPORTS_VIEW);

        $request->validate([
            'year_id' => 'required|exists:academic_years,id',
            'from'    => 'nullable|date',
            'to'      => 'nullable|date|after_or_equal:from',
        ]);

        $year = AcademicYear::findOrFail($request->year_id);

        $rows = DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->join('students', 'students.id', '=', 'invoices.student_id')
            ->where('invoices.academic_year_id', $year->id)
            ->when($request->from, fn($q) => $q->where('payments.created_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->where('payments.created_at', '<=', $request->to . ' 23:59:59'))
            ->select(
                'students.student_code',
                'students.name_en',
                'invoices.number as invoice_number',
                'invoices.total as total_amount',
                'invoices.status as invoice_status',
                'payments.amount as payment_amount',
                'payments.method',
                DB::raw('date(payments.paid_at) as paid_date')
            )
            ->orderBy('payments.created_at', 'desc')
            ->get();

        $totalCollected = $rows->sum('payment_amount');

        if ($request->format === 'pdf') {
            return $this->pdfResponse('reports.pdf.fee', compact('year', 'rows', 'totalCollected'), "fees-{$year->name}.pdf");
        }

        if ($request->format === 'excel') {
            return $this->excelResponse($rows, "fees-{$year->name}.xlsx", [
                'Student Code', 'Name', 'Invoice #', 'Invoice Total', 'Status', 'Paid', 'Method', 'Date',
            ], ['student_code', 'name_en', 'invoice_number', 'total_amount', 'invoice_status', 'payment_amount', 'method', 'paid_date']);
        }

        return view('reports.fee', compact('year', 'rows', 'totalCollected'));
    }

    private function pdfResponse(string $view, array $data, string $filename)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data);
        return $pdf->download($filename);
    }

    private function excelResponse($rows, string $filename, array $headers, array $columns)
    {
        $callback = function () use ($rows, $headers, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                $line = [];
                foreach ($columns as $col) {
                    $line[] = is_object($row) ? ($row->$col ?? '') : ($row[$col] ?? '');
                }
                fputcsv($handle, $line);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
