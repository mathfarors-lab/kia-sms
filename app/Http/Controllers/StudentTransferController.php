<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\TransferStudentRequest;
use App\Http\Requests\Student\WithdrawStudentRequest;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Services\StudentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentTransferController extends Controller
{
    public function __construct(private StudentService $studentService) {}

    public function transferForm(Student $student)
    {
        $this->authorize('students.edit');

        $branches = Branch::where('id', '!=', $student->branch_id)->orderBy('name_en')->get();
        $outstandingBalance = $this->outstandingBalance($student);

        return view('students.transfer', compact('student', 'branches', 'outstandingBalance'));
    }

    public function transfer(TransferStudentRequest $request, Student $student)
    {
        return $this->process($request, $student, StudentTransfer::TYPE_TRANSFER, 'transferred');
    }

    public function withdrawForm(Student $student)
    {
        $this->authorize('students.edit');

        $outstandingBalance = $this->outstandingBalance($student);

        return view('students.withdraw', compact('student', 'outstandingBalance'));
    }

    public function withdraw(WithdrawStudentRequest $request, Student $student)
    {
        return $this->process($request, $student, StudentTransfer::TYPE_WITHDRAWAL, 'dropped');
    }

    /**
     * Shared by transfer() and withdraw(): surface the outstanding balance
     * (never block on it), require an explicit acknowledgement when one
     * exists, then hand off to StudentService::update() — the SAME status
     * update path a direct student edit uses, so the existing leaving-
     * certificate auto-issuance in StudentService fires identically.
     */
    private function process(Request $request, Student $student, string $type, string $newStatus)
    {
        $data = $request->validated();
        $outstandingBalance = $this->outstandingBalance($student);

        if ($outstandingBalance > 0 && !$request->boolean('acknowledge_balance')) {
            return back()
                ->withErrors(['acknowledge_balance' => __('student_transfer.acknowledge_balance_required')])
                ->withInput();
        }

        $this->studentService->update($student, ['status' => $newStatus], null);

        StudentTransfer::create([
            'student_id'                  => $student->id,
            'type'                        => $type,
            'reason_category'             => $data['reason_category'],
            'reason_note'                 => $data['reason_note'] ?? null,
            'effective_date'              => $data['effective_date'],
            'destination_branch_id'       => $data['destination_branch_id'] ?? null,
            'destination_name'            => $data['destination_name'] ?? null,
            'outstanding_balance_at_time' => $outstandingBalance > 0 ? $outstandingBalance : null,
            'created_by'                  => Auth::id(),
        ]);

        $message = $type === StudentTransfer::TYPE_TRANSFER
            ? __('student_transfer.transfer_completed')
            : __('student_transfer.withdrawal_completed');

        return redirect()->route('students.show', $student)->with('success', $message);
    }

    private function outstandingBalance(Student $student): float
    {
        return Invoice::where('student_id', $student->id)
            ->unpaid()
            ->get()
            ->sum(fn ($invoice) => (float) $invoice->remainingBalance());
    }
}
