<?php

namespace App\Support;

/**
 * Holds the branch the current request operates in.
 *
 * Resolution (set by SetBranchContext middleware):
 *   - owner        → the branch chosen in the topbar switcher (session)
 *   - everyone else → their own users.branch_id
 *   - console / queue / unauthenticated / legacy users → null = UNSCOPED
 *
 * BranchScope only filters queries when a context is set, so artisan
 * commands, jobs, and every pre-multi-branch test run exactly as before.
 */
final class BranchContext
{
    private static ?int $branchId = null;

    public static function set(?int $branchId): void
    {
        self::$branchId = $branchId;
    }

    public static function current(): ?int
    {
        return self::$branchId;
    }

    public static function clear(): void
    {
        self::$branchId = null;
    }

    /** Run a callback under a specific branch context, restoring the previous one after. */
    public static function within(?int $branchId, callable $callback): mixed
    {
        $previous = self::$branchId;
        self::$branchId = $branchId;

        try {
            return $callback();
        } finally {
            self::$branchId = $previous;
        }
    }
}
