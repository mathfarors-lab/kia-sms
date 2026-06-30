<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Student permissions
            'students.view', 'students.create', 'students.edit', 'students.delete',
            // Staff permissions
            'staff.view', 'staff.create', 'staff.edit', 'staff.delete',
            // Academic
            'academic-years.manage', 'classes.manage', 'sections.manage',
            'subjects.manage', 'timetables.manage',
            // Attendance
            'attendance.view', 'attendance.mark',
            // Exams
            'exams.manage', 'marks.enter', 'marks.view', 'results.publish',
            // Finance
            'invoices.view', 'invoices.create', 'invoices.manage',
            'payments.record', 'fee-structures.manage',
            // Library
            'books.manage', 'book-issues.manage',
            // Settings
            'settings.manage', 'users.manage',
            // Admissions
            'admissions.view', 'admissions.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $roles = [
            'admin' => $permissions, // all permissions

            'principal' => [
                'students.view', 'students.edit',
                'staff.view',
                'academic-years.manage', 'classes.manage', 'sections.manage', 'subjects.manage',
                'attendance.view', 'attendance.mark',
                'exams.manage', 'marks.view', 'results.publish',
                'invoices.view', 'fee-structures.manage',
                'admissions.view', 'admissions.manage',
            ],

            'teacher' => [
                'students.view',
                'attendance.view', 'attendance.mark',
                'marks.enter', 'marks.view',
            ],

            'accountant' => [
                'students.view',
                'invoices.view', 'invoices.create', 'invoices.manage',
                'payments.record', 'fee-structures.manage',
            ],

            'librarian' => [
                'students.view',
                'books.manage', 'book-issues.manage',
            ],

            'receptionist' => [
                'students.view', 'students.create', 'students.edit',
                'admissions.view', 'admissions.manage',
            ],

            'student' => [
                'attendance.view',
                'marks.view',
            ],

            'parent' => [
                'attendance.view',
                'marks.view',
                'invoices.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
