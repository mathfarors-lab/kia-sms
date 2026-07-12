<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\Scopes\BranchScope;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Branch multi-tenancy for a model:
 *  - every query is filtered to the active branch (when a context is set),
 *  - every new row is stamped with the active branch automatically.
 *
 * Cross-branch access (owner consolidated views, system jobs) must be
 * explicit: Model::withoutGlobalScope(BranchScope::class).
 */
trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            if ($model->branch_id === null) {
                $model->branch_id = BranchContext::current();
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
