<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StudentService
{
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

        return Student::create($data);
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
