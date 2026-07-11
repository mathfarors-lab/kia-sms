<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;

class TransportPolicy
{
    public function viewAny(User $user): bool { return $user->can(Permissions::TRANSPORT_VIEW); }
    public function manage(User $user): bool  { return $user->can(Permissions::TRANSPORT_MANAGE); }
}
