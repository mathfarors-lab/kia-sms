<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StaffService
{
    public function generateCode(): string
    {
        $last = Staff::withTrashed()->orderByDesc('id')->value('staff_code');
        $next = $last ? (int) substr($last, strrpos($last, '-') + 1) + 1 : 1;
        return 'STF-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function store(array $data, ?UploadedFile $photo = null): Staff
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
            'photo'      => $photo?->store('staff/photos', 'local'),
        ]);
    }

    public function update(Staff $staff, array $data, ?UploadedFile $photo = null): Staff
    {
        $staff->user->update([
            'name'  => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (!empty($data['role'])) {
            $staff->user->syncRoles([$data['role']]);
        }

        $attrs = [
            'position'   => $data['position'] ?? null,
            'department' => $data['department'] ?? null,
            'joined_at'  => $data['joined_at'] ?? null,
            'salary'     => $data['salary'] ?? null,
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
