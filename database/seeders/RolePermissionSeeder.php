<?php

namespace Database\Seeders;

use App\Support\Permissions as P;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create every permission from the single source of truth
        foreach (P::all() as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $roles = [
            'admin' => P::all(),

            'principal' => [
                P::STUDENTS_VIEW, P::STUDENTS_EDIT,
                P::STAFF_VIEW,
                P::ACADEMIC_YEARS_MANAGE, P::CLASSES_MANAGE, P::SECTIONS_MANAGE, P::SUBJECTS_MANAGE,
                P::ATTENDANCE_VIEW, P::ATTENDANCE_MARK,
                P::EXAMS_VIEW, P::EXAMS_MANAGE, P::EXAMS_PUBLISH, P::MARKS_VIEW,
                P::INVOICES_VIEW, P::FEES_MANAGE,
                P::ADMISSIONS_VIEW, P::ADMISSIONS_MANAGE,
                P::SETTINGS_MANAGE,
                P::ANNOUNCEMENTS_VIEW, P::ANNOUNCEMENTS_CREATE, P::ANNOUNCEMENTS_MANAGE,
                P::MESSAGES_SEND, P::MESSAGES_VIEW,
                P::BOOKS_VIEW, P::TRANSPORT_VIEW,
            ],

            'teacher' => [
                P::STUDENTS_VIEW,
                P::ATTENDANCE_VIEW, P::ATTENDANCE_MARK,
                P::EXAMS_VIEW, P::MARKS_ENTRY, P::MARKS_VIEW,
                P::ANNOUNCEMENTS_VIEW, P::ANNOUNCEMENTS_CREATE,
                P::MESSAGES_SEND, P::MESSAGES_VIEW,
                P::HOMEWORK_MANAGE, P::HOMEWORK_GRADE,
                P::BOOKS_VIEW,
            ],

            'accountant' => [
                P::STUDENTS_VIEW,
                P::INVOICES_VIEW, P::INVOICES_CREATE, P::INVOICES_MANAGE,
                P::PAYMENTS_RECORD, P::FEES_MANAGE,
                P::MESSAGES_SEND, P::MESSAGES_VIEW,
            ],

            'librarian' => [
                P::STUDENTS_VIEW,
                P::BOOKS_MANAGE, P::BOOKS_VIEW,
                P::BOOK_ISSUES_MANAGE, P::BOOK_ISSUES_VIEW,
            ],

            'receptionist' => [
                P::STUDENTS_VIEW, P::STUDENTS_CREATE, P::STUDENTS_EDIT,
                P::ADMISSIONS_VIEW, P::ADMISSIONS_MANAGE,
                P::TRANSPORT_MANAGE, P::TRANSPORT_VIEW,
                P::MESSAGES_SEND, P::MESSAGES_VIEW,
            ],

            'student' => [
                P::ATTENDANCE_VIEW,
                P::EXAMS_VIEW, P::MARKS_VIEW,
                P::INVOICES_VIEW,
                P::ANNOUNCEMENTS_VIEW,
                P::MESSAGES_SEND, P::MESSAGES_VIEW,
                P::HOMEWORK_SUBMIT,
                P::BOOKS_VIEW, P::BOOK_ISSUES_VIEW,
                P::TRANSPORT_VIEW,
            ],

            'parent' => [
                P::ATTENDANCE_VIEW,
                P::EXAMS_VIEW, P::MARKS_VIEW,
                P::INVOICES_VIEW,
                P::ANNOUNCEMENTS_VIEW,
                P::MESSAGES_SEND, P::MESSAGES_VIEW,
                P::BOOKS_VIEW,
                P::TRANSPORT_VIEW,
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
