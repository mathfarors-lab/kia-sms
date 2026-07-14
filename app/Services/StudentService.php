<?php

namespace App\Services;

use App\Models\IssuedDocument;
use App\Models\Student;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StudentService
{
    public function __construct(private DocumentIssuanceService $documents) {}

    /**
     * student_code is globally unique in the DB, but the lookup query below
     * is branch-scoped automatically (BelongsToBranch) — so two branches
     * would each compute the same "next" number from their own view. Once a
     * branch context is active, the branch code is embedded in the returned
     * string (KIA-MC-26-0001) so the two branches' outputs can never collide
     * even though their sequences run independently. No context (console,
     * pre-M1 installs) keeps the original KIA-26-0001 format.
     */
    public function generateCode(): string
    {
        $prefix   = Setting::get('student_code_prefix', 'KIA');
        $year     = now()->format('y');
        $branchId = \App\Support\BranchContext::current();

        $last = Student::withTrashed()
            ->where('student_code', 'like', "{$prefix}-%-{$year}-%")
            ->orWhere(fn ($q) => $q->where('student_code', 'like', "{$prefix}-{$year}-%"))
            ->orderByDesc('id')
            ->value('student_code');

        $next = $last
            ? (int) substr($last, strrpos($last, '-') + 1) + 1
            : 1;

        $branchCode = $branchId ? \App\Models\Branch::find($branchId)?->code : null;
        $middle     = $branchCode ? "{$branchCode}-{$year}" : $year;

        return "{$prefix}-{$middle}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function store(array $data, ?UploadedFile $photo = null): Student
    {
        $data['student_code'] = $this->generateCode();

        if ($photo) {
            $data['photo'] = $photo->store('students/photos', 'local');
        }

        $student = Student::create($data);

        // Both direct creation and admission conversion (which calls this
        // same store()) land here — one hook covers both trigger points.
        if ($student->status === 'enrolled') {
            $this->documents->issueForStudent($student, IssuedDocument::TYPE_ID_CARD);
            $this->documents->issueForStudent($student, IssuedDocument::TYPE_ENROLLMENT_CERT);
        }

        return $student;
    }

    public function update(Student $student, array $data, ?UploadedFile $photo = null): Student
    {
        if ($photo) {
            if ($student->photo) {
                Storage::disk('local')->delete($student->photo);
            }
            $data['photo'] = $photo->store('students/photos', 'local');
        }

        $student->update($data);

        // Covers a manual status edit (the edit form allows any of the four
        // statuses) — PromotionService::execute() covers the bulk-rollover path.
        if ($student->wasChanged('status')) {
            match ($student->status) {
                'graduated'                => $this->documents->issueForStudent($student, IssuedDocument::TYPE_GRADUATION_CERT),
                'transferred', 'dropped'   => $this->documents->issueForStudent($student, IssuedDocument::TYPE_LEAVING_CERT),
                default                    => null,
            };
        }

        return $student;
    }

    public function destroy(Student $student): void
    {
        if ($student->photo) {
            Storage::disk('local')->delete($student->photo);
        }
        $student->delete();
    }
}
