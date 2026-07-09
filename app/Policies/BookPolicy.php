<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;

class BookPolicy
{
    public function viewAny(User $user): bool  { return $user->can(Permissions::BOOKS_VIEW); }
    public function view(User $user): bool     { return $user->can(Permissions::BOOKS_VIEW); }

    public function create(User $user): bool   { return $user->hasAnyRole(['admin', 'librarian']); }
    public function update(User $user): bool   { return $user->hasAnyRole(['admin', 'librarian']); }
    public function delete(User $user): bool   { return $user->hasAnyRole(['admin', 'librarian']); }
    public function issue(User $user): bool    { return $user->hasAnyRole(['admin', 'librarian']); }
    public function return_(User $user): bool  { return $user->hasAnyRole(['admin', 'librarian']); }
}
