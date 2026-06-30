<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MessagingService
{
    /**
     * Whether $sender is allowed to initiate a conversation with $recipient.
     *
     * Rules:
     *  - admin ↔ anyone
     *  - principal ↔ anyone
     *  - teacher ↔ parents/students of their own sections
     *  - parent ↔ their children's teachers + office roles (admin, accountant, receptionist)
     *  - student ↔ their teachers + office roles
     */
    public function canMessage(User $sender, User $recipient): bool
    {
        if ($sender->id === $recipient->id) return false;
        if ($sender->hasAnyRole(['admin', 'principal'])) return true;
        if ($recipient->hasAnyRole(['admin', 'principal'])) return true;

        // Teacher may message parents/students of their sections
        if ($sender->hasRole('teacher')) {
            return $this->teacherCanReach($sender, $recipient);
        }

        // Parent may message their children's teachers + office
        if ($sender->hasRole('parent')) {
            if ($recipient->hasAnyRole(['accountant', 'receptionist'])) return true;
            if ($recipient->hasRole('teacher')) {
                return $this->teacherCanReach($recipient, $sender); // symmetric
            }
        }

        // Student may message their teachers + office
        if ($sender->hasRole('student')) {
            if ($recipient->hasAnyRole(['accountant', 'receptionist'])) return true;
            if ($recipient->hasRole('teacher')) {
                return $this->studentTeacherRelated($sender, $recipient);
            }
        }

        return false;
    }

    public function createConversation(User $creator, User $recipient, string $subject, string $body): Conversation
    {
        if (! $this->canMessage($creator, $recipient)) {
            abort(403, __('engagement.messaging_not_allowed'));
        }

        return DB::transaction(function () use ($creator, $recipient, $subject, $body) {
            $conv = Conversation::create(['subject' => $subject, 'created_by' => $creator->id]);
            $conv->participants()->attach([$creator->id, $recipient->id]);

            Message::create([
                'conversation_id' => $conv->id,
                'sender_id'       => $creator->id,
                'body'            => $body,
            ]);

            return $conv;
        });
    }

    public function reply(Conversation $conv, User $sender, string $body): Message
    {
        if (! $conv->hasParticipant($sender)) {
            abort(403);
        }

        return DB::transaction(function () use ($conv, $sender, $body) {
            $msg = Message::create([
                'conversation_id' => $conv->id,
                'sender_id'       => $sender->id,
                'body'            => $body,
            ]);

            // Mark all other participants' last_read_at as null (unread for them)
            $conv->participants()
                ->where('users.id', '!=', $sender->id)
                ->update(['last_read_at' => null]);

            return $msg;
        });
    }

    public function markRead(Conversation $conv, User $user): void
    {
        $conv->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);
    }

    // --- private helpers ---

    private function teacherCanReach(User $teacher, User $other): bool
    {
        $staff = $teacher->staff;
        if (! $staff) return false;

        // Sections this teacher teaches
        $sectionIds = DB::table('timetables')
            ->where('teacher_id', $staff->id)
            ->pluck('section_id');

        if ($other->hasRole('student') && $other->student) {
            return DB::table('student_section')
                ->whereIn('section_id', $sectionIds)
                ->where('student_id', $other->student->id)
                ->exists();
        }

        if ($other->hasRole('parent')) {
            $wardIds = $other->wards()->pluck('students.id');
            return DB::table('student_section')
                ->whereIn('section_id', $sectionIds)
                ->whereIn('student_id', $wardIds)
                ->exists();
        }

        return false;
    }

    private function studentTeacherRelated(User $student, User $teacher): bool
    {
        if (! $student->student || ! $teacher->staff) return false;

        $sectionIds = DB::table('student_section')
            ->where('student_id', $student->student->id)
            ->pluck('section_id');

        return DB::table('timetables')
            ->whereIn('section_id', $sectionIds)
            ->where('teacher_id', $teacher->staff->id)
            ->exists();
    }
}
