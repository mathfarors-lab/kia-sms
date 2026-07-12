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

    /**
     * Apply the active branch filter to a raw query/Eloquent builder.
     * Analytics and report code runs DB::table() queries that the Eloquent
     * BranchScope cannot reach — every such query must filter through this
     * (or an equivalent explicit ->where(branch_id)) or it silently leaks
     * every branch's data to a branch-scoped user.
     *
     * @template TQuery
     * @param TQuery $query
     * @return TQuery
     */
    public static function apply($query, string $column = 'branch_id')
    {
        return self::$branchId !== null ? $query->where($column, self::$branchId) : $query;
    }

    /** Cache-key suffix so branch-scoped and global results never share a cache slot. */
    public static function cacheKeySuffix(): string
    {
        return self::$branchId !== null ? (string) self::$branchId : 'global';
    }
}
