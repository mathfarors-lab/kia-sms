<?php

namespace App\Models;

use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolDocument extends Model
{
    const CATEGORIES = ['policy', 'form', 'template', 'other'];

    protected $fillable = ['title', 'category', 'path', 'original_name', 'uploaded_by', 'branch_id'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Deliberately not the BelongsToBranch trait — its global scope does
     * strict branch_id equality, so a null (all-branches) row would be
     * invisible to any branch-scoped user, the opposite of what's wanted
     * here. Mirrors Setting::allForCurrentBranch() instead: a document's
     * own branch's rows plus every school-wide (branch_id IS NULL) row.
     */
    public function scopeVisibleToBranch($query, ?int $branchId = null)
    {
        $branchId ??= BranchContext::current();

        return $query->where(function ($q) use ($branchId) {
            $q->whereNull('branch_id');
            if ($branchId !== null) {
                $q->orWhere('branch_id', $branchId);
            }
        });
    }
}
