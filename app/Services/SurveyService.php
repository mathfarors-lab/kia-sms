<?php

namespace App\Services;

use App\Jobs\SendSurveyNotifications;
use App\Models\Section;
use App\Models\Student;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SurveyService
{
    public function publish(Survey $survey): void
    {
        $survey->update([
            'status' => 'open',
            'opens_at' => $survey->opens_at ?? now(),
        ]);

        // Dispatch chunked fan-out job — never fan out synchronously (same
        // discipline as AnnouncementService::publish()).
        SendSurveyNotifications::dispatch($survey->id);
    }

    public function close(Survey $survey): void
    {
        $survey->update(['status' => 'closed']);
    }

    /**
     * Collect the users targeted by this survey's audience. Mirrors
     * AnnouncementService::recipientQuery()'s shape, extended to the 5
     * audience dimensions this feature needs (Announcement only has 3).
     */
    public function recipientQuery(Survey $survey): Builder
    {
        $query = User::query()->where('status', 'active');

        if ($survey->audience === 'all') {
            return $query;
        }

        if ($survey->audience === 'role') {
            $role = Role::find($survey->target_id);

            return $role ? $query->role($role) : $query->whereRaw('1=0');
        }

        if ($survey->audience === 'branch') {
            return $query->where('branch_id', $survey->target_id);
        }

        if ($survey->audience === 'class') {
            // target_id = school_class_id
            $sectionIds = Section::where('school_class_id', $survey->target_id)->pluck('id');

            return $query->whereIn('id', $this->userIdsForSections($sectionIds));
        }

        if ($survey->audience === 'section') {
            // target_id = section_id
            return $query->whereIn('id', $this->userIdsForSections(collect([$survey->target_id])));
        }

        return $query->whereRaw('1=0');
    }

    /** Students in the given sections, plus their guardians. */
    private function userIdsForSections(Collection $sectionIds): Collection
    {
        $studentIds = DB::table('student_section')->whereIn('section_id', $sectionIds)->pluck('student_id');

        $userIds = Student::whereIn('id', $studentIds)->pluck('user_id');
        $parentIds = DB::table('student_guardian')->whereIn('student_id', $studentIds)->pluck('guardian_id');

        return $userIds->merge($parentIds)->unique();
    }
}
