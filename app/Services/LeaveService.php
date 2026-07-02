<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function submit(User $user, array $data): Leave
    {
        $leave = new Leave();
        if ($leave->overlaps($user->id, $data['start_date'], $data['end_date'])) {
            throw ValidationException::withMessages([
                'start_date' => [__('leave.overlaps')],
            ]);
        }

        return Leave::create([
            'user_id'    => $user->id,
            'type'       => $data['type'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'reason'     => $data['reason'] ?? null,
            'status'     => 'pending',
        ]);
    }

    public function approve(Leave $leave, User $reviewer): Leave
    {
        if ($leave->user_id === $reviewer->id) {
            throw ValidationException::withMessages([
                'leave_id' => [__('leave.cannot_approve_own')],
            ]);
        }

        $leave->update([
            'status'      => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $leave->fresh();
    }

    public function reject(Leave $leave, User $reviewer, ?string $note = null): Leave
    {
        if ($leave->user_id === $reviewer->id) {
            throw ValidationException::withMessages([
                'leave_id' => [__('leave.cannot_approve_own')],
            ]);
        }

        $leave->update([
            'status'        => 'rejected',
            'reviewed_by'   => $reviewer->id,
            'reviewed_at'   => now(),
            'reviewer_note' => $note,
        ]);

        return $leave->fresh();
    }
}
