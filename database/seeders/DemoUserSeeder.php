<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        // All demo people belong to Main Campus (branch 1): the creating-hooks
        // stamp branch_id on every user/staff/student made inside this block.
        \App\Support\BranchContext::within(1, function () {
        $users = [
            [
                'name'   => 'Admin User',
                'email'  => 'admin@kia.edu.kh',
                'phone'  => '012000001',
                'role'   => 'admin',
                'locale' => 'en',
            ],
            [
                'name'   => 'Principal Sophea',
                'email'  => 'principal@kia.edu.kh',
                'phone'  => '012000002',
                'role'   => 'principal',
                'locale' => 'km',
            ],
            [
                'name'   => 'Teacher Dara',
                'email'  => 'teacher@kia.edu.kh',
                'phone'  => '012000003',
                'role'   => 'teacher',
                'locale' => 'km',
            ],
            [
                'name'   => 'Accountant Chenda',
                'email'  => 'accountant@kia.edu.kh',
                'phone'  => '012000004',
                'role'   => 'accountant',
                'locale' => 'km',
            ],
            [
                'name'   => 'Librarian Mony',
                'email'  => 'librarian@kia.edu.kh',
                'phone'  => '012000005',
                'role'   => 'librarian',
                'locale' => 'km',
            ],
            [
                'name'   => 'Receptionist Bopha',
                'email'  => 'receptionist@kia.edu.kh',
                'phone'  => '012000006',
                'role'   => 'receptionist',
                'locale' => 'km',
            ],
            [
                'name'   => 'Sokha Chea',
                'email'  => 'student@kia.edu.kh',
                'phone'  => '012000007',
                'role'   => 'student',
                'locale' => 'km',
            ],
            [
                'name'   => 'Parent Vanna',
                'email'  => 'parent@kia.edu.kh',
                'phone'  => '012000008',
                'role'   => 'parent',
                'locale' => 'km',
            ],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('password'),
                    'phone'    => $data['phone'],
                    'locale'   => $data['locale'],
                    'status'   => 'active',
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$data['role']]);

            // Create staff record for teacher/principal/accountant etc.
            if (in_array($data['role'], ['teacher', 'principal', 'accountant', 'librarian', 'receptionist'])) {
                Staff::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'staff_code'  => 'STF-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                        'position'    => ucfirst($data['role']),
                        'department'  => match($data['role']) {
                            'teacher'      => 'Academic',
                            'principal'    => 'Administration',
                            'accountant'   => 'Finance',
                            'librarian'    => 'Library',
                            'receptionist' => 'Reception',
                            default        => 'General',
                        },
                        'joined_at' => now()->subMonths(rand(6, 36)),
                        'salary'    => match($data['role']) {
                            'principal'  => 1500.00,
                            'teacher'    => 900.00,
                            'accountant' => 800.00,
                            default      => 600.00,
                        },
                    ]
                );
            }

            // Create student record
            if ($data['role'] === 'student') {
                Student::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'student_code'  => 'KIA-25-0001',
                        'name_en'       => $data['name'],
                        'name_km'       => 'ស្រីចន្ទបូ',
                        'gender'        => 'female',
                        'date_of_birth' => '2010-05-15',
                        'address'       => 'Phnom Penh, Cambodia',
                        'status'        => 'enrolled',
                    ]
                );
            }
        }

        // Add 5 more demo students
        $demoStudents = [
            ['name_en' => 'Dara Sok',      'name_km' => 'ដារ៉ា សុខ',     'gender' => 'male',   'dob' => '2009-03-10', 'code' => 'KIA-25-0002'],
            ['name_en' => 'Sreymom Chan',  'name_km' => 'ស្រីម៉ម ចន',   'gender' => 'female', 'dob' => '2010-07-22', 'code' => 'KIA-25-0003'],
            ['name_en' => 'Pisach Nhem',   'name_km' => 'ពិសាច ញ៉ែម',   'gender' => 'male',   'dob' => '2008-11-05', 'code' => 'KIA-25-0004'],
            ['name_en' => 'Chantha Keo',   'name_km' => 'ចន្ទា កែវ',     'gender' => 'female', 'dob' => '2011-01-30', 'code' => 'KIA-25-0005'],
            ['name_en' => 'Vibol Phim',    'name_km' => 'វិបុល ភីម',     'gender' => 'male',   'dob' => '2009-09-18', 'code' => 'KIA-25-0006'],
        ];

        foreach ($demoStudents as $s) {
            Student::updateOrCreate(
                ['student_code' => $s['code']],
                [
                    'name_en'       => $s['name_en'],
                    'name_km'       => $s['name_km'],
                    'gender'        => $s['gender'],
                    'date_of_birth' => $s['dob'],
                    'address'       => 'Phnom Penh, Cambodia',
                    'status'        => 'enrolled',
                ]
            );
        }
        }); // end BranchContext::within(1)

        // Owner (superadmin) — deliberately NO branch: reaches every branch
        // through the topbar switcher instead of being locked to one.
        $owner = User::updateOrCreate(
            ['email' => 'owner@kia.edu.kh'],
            [
                'name'              => 'Owner Sovann',
                'password'          => Hash::make('password'),
                'phone'             => '012000000',
                'locale'            => 'en',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );
        $owner->forceFill(['branch_id' => null])->save();
        $owner->syncRoles(['owner']);
    }
}
