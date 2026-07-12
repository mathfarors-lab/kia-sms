<?php

namespace App\Models;

use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'branch_id'];

    /**
     * Branch-aware lookup: the active branch's own value wins; a global row
     * (branch_id NULL) is the fallback; then the code default. Cached per
     * branch so two branches can hold different values for the same key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $branchId = BranchContext::current();

        return Cache::rememberForever("setting:{$branchId}:{$key}", function () use ($key, $branchId, $default) {
            if ($branchId !== null) {
                $branchValue = static::where('key', $key)->where('branch_id', $branchId)->value('value');
                if ($branchValue !== null) {
                    return $branchValue;
                }
            }

            return static::where('key', $key)->whereNull('branch_id')->value('value') ?? $default;
        });
    }

    /**
     * Writes to the active branch when one is set (branch admins tune their
     * own campus), otherwise to the global fallback row (console/seeders).
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        $branchId = BranchContext::current();

        static::updateOrCreate(
            ['key' => $key, 'branch_id' => $branchId],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("setting:{$branchId}:{$key}");
        if ($branchId !== null) {
            return;
        }

        // A global write can change what every branch inherits — drop their
        // cached copies too.
        foreach (Branch::pluck('id') as $id) {
            Cache::forget("setting:{$id}:{$key}");
        }
    }
}
