<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookIssue;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LibrarianDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Cache::flush();
    }

    private function makeLibrarian(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('librarian');
        return $user;
    }

    private function makeBook(): Book
    {
        return Book::create([
            'title' => 'Book-' . uniqid(), 'author' => 'Author', 'category' => 'Fiction',
            'total_copies' => 5, 'available_copies' => 5,
        ]);
    }

    private function makeStudent(): Student
    {
        return Student::create(['student_code' => 'S-' . uniqid(), 'name_en' => 'Reader-' . uniqid(), 'gender' => 'male', 'status' => 'enrolled']);
    }

    private function makeIssue(Book $book, User $issuedBy, array $attrs = []): BookIssue
    {
        return BookIssue::create(array_merge([
            'book_id' => $book->id, 'student_id' => $this->makeStudent()->id, 'issued_by' => $issuedBy->id,
            'issued_at' => now(), 'due_date' => now()->addDays(7),
        ], $attrs));
    }

    public function test_dashboard_shows_correct_overdue_count(): void
    {
        $librarian = $this->makeLibrarian();
        $book      = $this->makeBook();

        // 2 overdue.
        $this->makeIssue($book, $librarian, ['issued_at' => now()->subDays(20), 'due_date' => now()->subDays(6)]);
        $this->makeIssue($book, $librarian, ['issued_at' => now()->subDays(15), 'due_date' => now()->subDays(1)]);
        // Not overdue: due in the future.
        $this->makeIssue($book, $librarian, ['issued_at' => now(), 'due_date' => now()->addDays(7)]);
        // Not overdue: already returned, even though past its due date.
        $this->makeIssue($book, $librarian, ['issued_at' => now()->subDays(20), 'due_date' => now()->subDays(5), 'returned_at' => now()->subDay()]);

        $html = $this->actingAs($librarian)
            ->get(route('dashboard.librarian'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('staff_dashboard.overdue_books'), $html);
        // 2 overdue: value renders in its own element, not concatenated with the label.
        $this->assertStringContainsString('>2<', $html);
    }

    public function test_dashboard_shows_correct_total_and_currently_issued_counts(): void
    {
        $librarian = $this->makeLibrarian();
        $this->makeBook();
        $this->makeBook();
        $this->makeBook();

        $book = $this->makeBook(); // 4th book
        $this->makeIssue($book, $librarian, ['issued_at' => now(), 'due_date' => now()->addDays(7)]);
        $this->makeIssue($book, $librarian, ['issued_at' => now()->subDays(30), 'due_date' => now()->subDays(23), 'returned_at' => now()->subDays(10)]);

        $html = $this->actingAs($librarian)
            ->get(route('dashboard.librarian'))
            ->assertOk()
            ->getContent();

        // 4 books total, 1 currently issued (the returned one doesn't count).
        $this->assertStringContainsString('>4<', $html);
        $this->assertStringContainsString('>1<', $html);
    }

    public function test_overdue_list_page_shows_only_overdue_issues(): void
    {
        $librarian = $this->makeLibrarian();
        $book = $this->makeBook();

        $overdueStudent = $this->makeStudent();
        BookIssue::create(['book_id' => $book->id, 'student_id' => $overdueStudent->id, 'issued_by' => $librarian->id, 'issued_at' => now()->subDays(20), 'due_date' => now()->subDays(6)]);

        $notOverdueStudent = $this->makeStudent();
        BookIssue::create(['book_id' => $book->id, 'student_id' => $notOverdueStudent->id, 'issued_by' => $librarian->id, 'issued_at' => now(), 'due_date' => now()->addDays(7)]);

        $html = $this->actingAs($librarian)
            ->get(route('book-issues.overdue'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($overdueStudent->name_en, $html);
        $this->assertStringNotContainsString($notOverdueStudent->name_en, $html);
    }

    public function test_role_without_book_issues_view_gets_403_on_overdue_list(): void
    {
        $accountant = User::factory()->create(['status' => 'active']);
        $accountant->assignRole('accountant'); // no book-issues.view permission

        $this->actingAs($accountant)
            ->get(route('book-issues.overdue'))
            ->assertForbidden();
    }

    public function test_dashboard_has_quick_links(): void
    {
        $librarian = $this->makeLibrarian();

        $this->actingAs($librarian)
            ->get(route('dashboard.librarian'))
            ->assertOk()
            ->assertSee(route('books.index'), false)
            ->assertSee(route('book-issues.overdue'), false);
    }
}
