<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // all authenticated users see the list (scoped by Announcement::scopeVisibleTo)
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if ($user->hasAnyRole(['admin', 'principal'])) return true;
        if ($announcement->posted_by === $user->id) return true;

        // Published announcements: check audience scope
        if ($announcement->published_at === null) return false;
        if ($announcement->audience === 'all') return true;

        // Delegate to the scope logic (re-query for this single record)
        return Announcement::visibleTo($user)->where('id', $announcement->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'principal', 'teacher']);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if ($announcement->published_at !== null) return false; // locked once published
        return $user->hasAnyRole(['admin', 'principal'])
            || $announcement->posted_by === $user->id;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->hasAnyRole(['admin', 'principal'])
            || $announcement->posted_by === $user->id;
    }

    public function publish(User $user, Announcement $announcement): bool
    {
        return $user->hasAnyRole(['admin', 'principal'])
            || $announcement->posted_by === $user->id;
    }
}
