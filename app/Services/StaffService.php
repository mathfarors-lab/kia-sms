<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StaffService
{
    public function generateCode(): string
    {
        $last = Staff::withTrashed()->orderByDesc('id')->value('staff_code');
        $next = $last ? (int) substr($last, strrpos($last, '-') + 1) + 1 : 1;
        return 'STF-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function store(array $data): Staff
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password'] ?? 'password'),
            'phone'    => $data['phone'] ?? null,
            'status'   => 'active',
        ]);

        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        return Staff::create([
            'user_id'    => $user->id,
            'staff_code' => $this->generateCode(),
            'position'   => $data['position'] ?? null,
            'department' => $data['department'] ?? null,
            'joined_at'  => $data['joined_at'] ?? now(),
            'salary'     => $data['salary'] ?? null,
        ]);
    }

    public function update(Staff $staff, array $data): Staff
    {
        $staff->user->update([
            'name'  => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (!empty($data['role'])) {
            $staff->user->syncRoles([$data['role']]);
        }

        $staff->update([
            'position'   => $data['position'] ?? null,
            'department' => $data['department'] ?? null,
            'joined_at'  => $data['joined_at'] ?? null,
            'salary'     => $data['salary'] ?? null,
        ]);

        return $staff;
    }
}
