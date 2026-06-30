<?php

namespace App\Services;

use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class HomeworkService
{
    private const DISK = 'local'; // private disk — served via gated route

    public function storeAttachment(UploadedFile $file, string $subdir): string
    {
        // Store with a generated filename — never trust the user's filename
        return $file->store($subdir, self::DISK);
    }

    public function submit(
        Homework $homework,
        Student $student,
        ?UploadedFile $file,
        ?string $note
    ): HomeworkSubmission {
        $submittedAt = now();
        $isLate = $homework->isLate($submittedAt);

        $filePath = $originalName = null;
        if ($file) {
            $filePath     = $this->storeAttachment($file, 'submissions');
            $originalName = $file->getClientOriginalName();
        }

        return HomeworkSubmission::updateOrCreate(
            ['homework_id' => $homework->id, 'student_id' => $student->id],
            [
                'file_path'          => $filePath,
                'file_original_name' => $originalName,
                'note'               => $note,
                'is_late'            => $isLate,
                'submitted_at'       => $submittedAt,
                // Clear any previous grading on resubmit
                'grade'    => null,
                'feedback' => null,
                'graded_by' => null,
                'graded_at' => null,
            ]
        );
    }

    public function grade(HomeworkSubmission $submission, Staff $grader, int $grade, ?string $feedback): HomeworkSubmission
    {
        $submission->update([
            'grade'     => $grade,
            'feedback'  => $feedback,
            'graded_by' => $grader->id,
            'graded_at' => now(),
        ]);

        return $submission->fresh();
    }

    public function downloadPath(string $storedPath): string
    {
        return Storage::disk(self::DISK)->path($storedPath);
    }
}
