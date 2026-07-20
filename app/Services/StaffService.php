<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Staff;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StaffService
{
    public function __construct(private DocumentIssuanceService $documents) {}

    /**
     * See StudentService::generateCode() for why the branch code gets
     * embedded once a branch context is active: staff_code is globally
     * unique but this lookup is branch-scoped, so two branches would
     * otherwise compute the same "next" number independently.
     */
    public function generateCode(): string
    {
        $branchId = BranchContext::current();

        $last = Staff::withTrashed()->orderByDesc('id')->value('staff_code');
        $next = $last ? (int) substr($last, strrpos($last, '-') + 1) + 1 : 1;

        $branchCode = $branchId ? Branch::find($branchId)?->code : null;
        $prefix = $branchCode ? "STF-{$branchCode}" : 'STF';

        return "{$prefix}-".str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function store(array $data, ?UploadedFile $photo = null): Staff
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'] ?? 'password'),
            'phone' => $data['phone'] ?? null,
            'status' => 'active',
        ]);

        if (! empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        $staff = Staff::create([
            'user_id' => $user->id,
            'staff_code' => $this->generateCode(),
            'position' => $data['position'] ?? null,
            'department' => $data['department'] ?? null,
            'joined_at' => $data['joined_at'] ?? now(),
            'salary' => $data['salary'] ?? null,
            'photo' => $photo?->store('staff/photos', 'local'),
            'contract_type' => $data['contract_type'] ?? null,
            'contract_end_date' => $data['contract_end_date'] ?? null,
            'employment_status' => $data['employment_status'] ?? 'active',
        ]);

        $this->documents->issueForStaff($staff);

        return $staff;
    }

    public function update(Staff $staff, array $data, ?UploadedFile $photo = null): Staff
    {
        $staff->user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (! empty($data['role'])) {
            $staff->user->syncRoles([$data['role']]);
        }

        $attrs = [
            'position' => $data['position'] ?? null,
            'department' => $data['department'] ?? null,
            'joined_at' => $data['joined_at'] ?? null,
            'salary' => $data['salary'] ?? null,
            'contract_type' => $data['contract_type'] ?? null,
            'contract_end_date' => $data['contract_end_date'] ?? null,
            'employment_status' => $data['employment_status'] ?? $staff->employment_status,
        ];

        if ($photo) {
            if ($staff->photo) {
                Storage::disk('local')->delete($staff->photo);
            }
            $attrs['photo'] = $photo->store('staff/photos', 'local');
        }

        $staff->update($attrs);

        return $staff;
    }
}
