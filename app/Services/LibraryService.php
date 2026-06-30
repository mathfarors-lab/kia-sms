<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LibraryService
{
    public function issue(Book $book, Student $student, User $issuedBy, string $dueDate): BookIssue
    {
        return DB::transaction(function () use ($book, $student, $issuedBy, $dueDate) {
            // Lock the row — prevents two concurrent requests both seeing available > 0
            $book = Book::lockForUpdate()->findOrFail($book->id);

            if ($book->available_copies <= 0) {
                throw ValidationException::withMessages([
                    'book_id' => [__('engagement.book_unavailable')],
                ]);
            }

            $book->decrement('available_copies');

            return BookIssue::create([
                'book_id'    => $book->id,
                'student_id' => $student->id,
                'issued_by'  => $issuedBy->id,
                'issued_at'  => today(),
                'due_date'   => $dueDate,
                'fine_amount' => '0.00',
            ]);
        });
    }

    public function returnBook(BookIssue $issue): BookIssue
    {
        return DB::transaction(function () use ($issue) {
            $issue = BookIssue::lockForUpdate()->findOrFail($issue->id);

            if ($issue->returned_at !== null) {
                throw ValidationException::withMessages([
                    'issue_id' => [__('engagement.already_returned')],
                ]);
            }

            $returnedAt = today();
            $issue->returned_at = $returnedAt;

            // Fine calculation: days late × daily rate
            $daysLate = max(0, $issue->due_date->diffInDays($returnedAt, false));
            if ($daysLate > 0) {
                $rate = (string) Setting::get('library_fine_per_day', '0.50');
                $fine = bcmul((string) $daysLate, $rate, 2);
                $issue->fine_amount = $fine;
            }

            $issue->save();

            Book::lockForUpdate()->where('id', $issue->book_id)->increment('available_copies');

            return $issue;
        });
    }
}
