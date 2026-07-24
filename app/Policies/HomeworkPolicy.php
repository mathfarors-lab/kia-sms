<?php

namespace App\Policies;

use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HomeworkPolicy
{
    // Principal is deliberately view-only (HOMEWORK_VIEW): sees all homework and
    // submissions school-wide, but cannot create, update, or grade.
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'teacher']);
    }

    /** Can the user browse the homework list at all? */
    public function viewAny(User $user): bool
    {
        if ($user->hasAnyRole(['admin', 'principal', 'teacher'])) {
            return true;
        }

        return $user->hasRole('student') && $user->student !== null;
    }

    /**
     * Can the user open this specific homework (and, by extension, download
     * its attachment)? Teacher is scoped to their own sections (homeroom +
     * subject-taught), not just homework they personally created — a
     * subject teacher reasonably needs to see what else is assigned to a
     * class they teach. update()/grade() stay creator-only below; viewing
     * is the only thing widened here.
     */
    public function view(User $user, Homework $hw): bool
    {
        if ($user->hasAnyRole(['admin', 'principal'])) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $user->staff && $user->staff->accessibleSectionIds()->contains($hw->section_id);
        }

        if ($user->hasRole('student') && $user->student) {
            if (is_null($hw->published_at)) {
                return false;
            }

            return DB::table('student_section')
                ->where('section_id', $hw->section_id)
                ->where('student_id', $user->student->id)
                ->exists();
        }

        return false;
    }

    public function update(User $user, Homework $hw): bool
    {
        if ($user->hasRole('admin')) return true;
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
        if ($user->hasRole('admin')) return true;
        // Teacher must own the homework
        return $user->hasRole('teacher')
            && $user->staff
            && $submission->homework->teacher_id === $user->staff->id;
    }
}
