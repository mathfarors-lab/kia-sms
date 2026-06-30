<?php

namespace App\Policies;

use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HomeworkPolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'principal', 'teacher']);
    }

    public function update(User $user, Homework $hw): bool
    {
        if ($user->hasAnyRole(['admin', 'principal'])) return true;
        return $user->hasRole('teacher') && $user->staff && $hw->teacher_id === $user->staff->id;
    }

    /** Can the user submit to this homework? */
    public function submit(User $user, Homework $hw): bool
    {
        if (! $user->hasRole('student') || ! $user->student) return false;

        // Student must be enrolled in the homework's section
        return DB::table('student_section')
            ->where('section_id', $hw->section_id)
            ->where('student_id', $user->student->id)
            ->exists();
    }

    /** Can the teacher grade this submission? */
    public function grade(User $user, HomeworkSubmission $submission): bool
    {
        if ($user->hasAnyRole(['admin', 'principal'])) return true;
        // Teacher must own the homework
        return $user->hasRole('teacher')
            && $user->staff
            && $submission->homework->teacher_id === $user->staff->id;
    }
}
