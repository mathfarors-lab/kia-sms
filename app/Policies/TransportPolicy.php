<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;

class TransportPolicy
{
    // userCan (not raw $user->can()): this policy is evaluated by the sidebar on
    // every authenticated page, so an unprovisioned permission row must mean
    // "no" — not a PermissionDoesNotExist 500 across the whole app.
    public function viewAny(User $user): bool { return Permissions::userCan($user, Permissions::TRANSPORT_VIEW); }
    public function manage(User $user): bool  { return Permissions::userCan($user, Permissions::TRANSPORT_MANAGE); }
}
