<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        return $user;
    }

    public function test_role_without_books_view_gets_403_on_books_index(): void
    {
        // accountant is seeded with no books.* permission at all.
        $accountant = $this->makeUser('accountant');

        $this->actingAs($accountant)
            ->get(route('books.index'))
            ->assertForbidden();
    }

    public function test_role_with_books_view_can_access_books_index(): void
    {
        $librarian = $this->makeUser('librarian');

        $this->actingAs($librarian)
            ->get(route('books.index'))
            ->assertOk();
    }

    // ── Issue-history scoping (books.show leaks other students' borrowing/fines) ──

    private function makeBookWithIssue(): array
    {
        $book = Book::create(['title' => 'Physics 101', 'total_copies' => 3, 'available_copies' => 2]);
        $student = Student::create([
            'student_code' => 'S-'.uniqid(), 'name_en' => 'Borrower Bopha',
            'gender' => 'female', 'status' => 'enrolled',
        ]);
        BookIssue::create([
            'book_id' => $book->id, 'student_id' => $student->id,
            'issued_by' => $this->makeUser('librarian')->id,
            'issued_at' => now()->subDays(3), 'due_date' => now()->addDays(11), 'fine_amount' => 0,
        ]);

        return [$book, $student];
    }

    public function test_librarian_sees_issue_history_on_book_page(): void
    {
        [$book, $student] = $this->makeBookWithIssue();

        $response = $this->actingAs($this->makeUser('librarian'))->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('Issue History');
        $response->assertSee($student->name_en);
    }

    public function test_teacher_does_not_see_other_students_issue_history_on_book_page(): void
    {
        [$book, $student] = $this->makeBookWithIssue();

        $response = $this->actingAs($this->makeUser('teacher'))->get(route('books.show', $book));

        $response->assertOk();
        $response->assertDontSee('Issue History');
        $response->assertDontSee($student->name_en);
    }

    public function test_student_does_not_see_other_students_issue_history_on_book_page(): void
    {
        [$book, $student] = $this->makeBookWithIssue();

        $response = $this->actingAs($this->makeUser('student'))->get(route('books.show', $book));

        $response->assertOk();
        $response->assertDontSee('Issue History');
        $response->assertDontSee($student->name_en);
    }
}
