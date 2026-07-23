<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;

class BookPolicy
{
    public function viewAny(User $user): bool  { return $user->can(Permissions::BOOKS_VIEW); }
    public function view(User $user): bool     { return $user->can(Permissions::BOOKS_VIEW); }

    /**
     * BOOKS_VIEW (catalog browsing) is held broadly — teacher, student,
     * receptionist. BOOK_ISSUES_MANAGE is the narrow librarian/admin-tier
     * permission for who actually manages checkouts, so it's also the
     * right gate for seeing OTHER students' borrowing history and fines
     * on a book's page, rather than the catalog-view permission itself.
     */
    public function viewIssueHistory(User $user): bool { return $user->can(Permissions::BOOK_ISSUES_MANAGE); }

    public function create(User $user): bool   { return $user->hasAnyRole(['admin', 'librarian']); }
    public function update(User $user): bool   { return $user->hasAnyRole(['admin', 'librarian']); }
    public function delete(User $user): bool   { return $user->hasAnyRole(['admin', 'librarian']); }
    public function issue(User $user): bool    { return $user->hasAnyRole(['admin', 'librarian']); }
    public function return_(User $user): bool  { return $user->hasAnyRole(['admin', 'librarian']); }
}
