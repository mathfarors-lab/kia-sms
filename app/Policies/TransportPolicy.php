<?php

namespace App\Policies;

use App\Models\User;

class TransportPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['admin', 'principal', 'receptionist']); }
    public function manage(User $user): bool  { return $user->hasAnyRole(['admin', 'receptionist']); }
}
