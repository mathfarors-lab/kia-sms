<?php

namespace App\Services;

use App\Models\AdmissionApplication;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionService
{
    public function __construct(private StudentService $students) {}

    /**
     * See StudentService::generateCode() for why the branch code gets
     * embedded once a branch context is active.
     */
    public function generateNumber(): string
    {
        $year     = now()->format('y');
        $branchId = \App\Support\BranchContext::current();

        $last = AdmissionApplication::where('application_no', 'like', "ADM-%{$year}-%")
            ->orderByDesc('id')
            ->value('application_no');

        $next = $last ? (int) substr($last, strrpos($last, '-') + 1) + 1 : 1;

        $branchCode = $branchId ? \App\Models\Branch::find($branchId)?->code : null;
        $middle     = $branchCode ? "{$branchCode}-{$year}" : $year;

        return "ADM-{$middle}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Convert an accepted application into an enrolled Student.
     * Idempotent: converting twice returns the same student, creates nothing new.
     * Enrollment into a specific section stays a manual follow-up step —
     * conversion creates the student record with an auto-generated code.
     *
     * @throws ValidationException when the application isn't in an acceptable state
     */
    public function convertToStudent(AdmissionApplication $application, int $convertedBy): Student
    {
        if ($application->student_id) {
            return $application->student; // already converted — no-op
        }

        if ($application->status !== 'accepted') {
            throw ValidationException::withMessages([
                'status' => __('admissions.only_accepted_can_convert'),
            ]);
        }

        return DB::transaction(function () use ($application, $convertedBy) {
            // Re-read under lock so two simultaneous converts can't both pass the guard.
            $fresh = AdmissionApplication::lockForUpdate()->findOrFail($application->id);
            if ($fresh->student_id) {
                return $fresh->student;
            }

            $student = $this->students->store([
                'name_en'       => $fresh->name_en,
                'name_km'       => $fresh->name_km,
                'gender'        => $fresh->gender,
                'date_of_birth' => $fresh->date_of_birth?->toDateString(),
                'address'       => $fresh->address,
                'status'        => 'enrolled',
            ]);

            $fresh->update([
                'student_id'  => $student->id,
                'status'      => 'converted',
                'reviewed_by' => $convertedBy,
                'reviewed_at' => now(),
            ]);

            return $student;
        });
    }
}
