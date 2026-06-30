<?php

namespace App\Services;

use App\Jobs\SendAnnouncementNotifications;
use App\Models\Announcement;
use App\Models\User;
use Carbon\Carbon;

class AnnouncementService
{
    public function publish(Announcement $announcement): void
    {
        $announcement->update(['published_at' => Carbon::now()]);

        // Dispatch chunked fan-out job — never fan out synchronously
        SendAnnouncementNotifications::dispatch($announcement->id);
    }

    /**
     * Collect the IDs of all users who should receive this announcement.
     * Used by the queued job for chunked dispatch.
     */
    public function recipientQuery(Announcement $announcement): \Illuminate\Database\Eloquent\Builder
    {
        $query = User::query()->where('status', 'active');

        if ($announcement->audience === 'all') {
            return $query;
        }

        if ($announcement->audience === 'class') {
            // target_id = section_id
            $studentIds = \DB::table('student_section')
                ->where('section_id', $announcement->target_id)
                ->pluck('student_id');

            $userIds = \App\Models\Student::whereIn('id', $studentIds)->pluck('user_id');

            // Also include parents of those students
            $parentIds = \DB::table('student_guardian')
                ->whereIn('student_id', $studentIds)
                ->pluck('guardian_id');

            return $query->whereIn('id', $userIds->merge($parentIds)->unique());
        }

        if ($announcement->audience === 'grade') {
            // target_id = school_class_id
            $sectionIds = \App\Models\Section::where('school_class_id', $announcement->target_id)->pluck('id');
            $studentIds = \DB::table('student_section')
                ->whereIn('section_id', $sectionIds)
                ->pluck('student_id');

            $userIds = \App\Models\Student::whereIn('id', $studentIds)->pluck('user_id');

            $parentIds = \DB::table('student_guardian')
                ->whereIn('student_id', $studentIds)
                ->pluck('guardian_id');

            return $query->whereIn('id', $userIds->merge($parentIds)->unique());
        }

        return $query->whereRaw('1=0'); // fallback: no recipients
    }
}
