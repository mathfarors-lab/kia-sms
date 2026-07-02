<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StudentService
{
    public function generateCode(): string
    {
        $prefix = Setting::get('student_code_prefix', 'KIA');
        $year   = now()->format('y');

        $last = Student::withTrashed()
            ->where('student_code', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->value('student_code');

        $next = $last
            ? (int) substr($last, strrpos($last, '-') + 1) + 1
            : 1;

        return "{$prefix}-{$year}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
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
