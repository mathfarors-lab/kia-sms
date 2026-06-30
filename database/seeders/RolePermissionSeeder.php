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
            'exams.view', 'exams.manage', 'exams.publish',
            'marks.entry', 'marks.view',
            // Finance
            'invoices.view', 'invoices.create', 'invoices.manage',
            'payments.record', 'fees.manage',
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
                'exams.view', 'exams.manage', 'exams.publish', 'marks.view',
                'invoices.view', 'fees.manage',
                'admissions.view', 'admissions.manage',
                'settings.manage',
            ],

            'teacher' => [
                'students.view',
                'attendance.view', 'attendance.mark',
                'exams.view', 'marks.entry', 'marks.view',
            ],

            'accountant' => [
                'students.view',
                'invoices.view', 'invoices.create', 'invoices.manage',
                'payments.record', 'fees.manage',
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
                'exams.view', 'marks.view',
            ],

            'parent' => [
                'attendance.view',
                'exams.view', 'marks.view',
                'invoices.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
