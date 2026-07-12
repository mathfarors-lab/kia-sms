<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToBranch;

class Announcement extends Model
{
    use BelongsToBranch;
    protected $fillable = [
        'title', 'body_en', 'body_km', 'audience', 'target_id',
        'posted_by', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    /** Scope to announcements visible to a given user (by audience). */
    public function scopeVisibleTo($query, User $user): void
    {
        // Staff roles see all published announcements
        if ($user->hasAnyRole(['admin', 'principal', 'teacher', 'accountant', 'librarian', 'receptionist'])) {
            $query->whereNotNull('published_at');
            return;
        }

        // Collect the student records for this user
        $students = collect();
        if ($user->hasRole('student') && $user->student) {
            $students->push($user->student);
        } elseif ($user->hasRole('parent')) {
            $students = $user->wards;
        }

        // From those students, collect section IDs and class IDs separately
        $sectionIds = collect();
        $classIds   = collect();

        foreach ($students as $student) {
            $ids = \DB::table('student_section')
                ->where('student_id', $student->id)
                ->pluck('section_id');
            $sectionIds = $sectionIds->merge($ids);
        }

        if ($sectionIds->isNotEmpty()) {
            $classIds = Section::whereIn('id', $sectionIds->unique())->pluck('school_class_id');
        }

        $sectionIds = $sectionIds->unique()->values();
        $classIds   = $classIds->unique()->values();

        $query->whereNotNull('published_at')
            ->where(function ($q) use ($sectionIds, $classIds) {
                $q->where('audience', 'all')
                  ->orWhere(function ($q2) use ($sectionIds) {
                      $q2->where('audience', 'class')->whereIn('target_id', $sectionIds);
                  })
                  ->orWhere(function ($q2) use ($classIds) {
                      $q2->where('audience', 'grade')->whereIn('target_id', $classIds);
                  });
            });
    }
}
