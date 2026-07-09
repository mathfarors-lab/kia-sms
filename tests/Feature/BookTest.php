<?php

namespace Tests\Feature;

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
}
