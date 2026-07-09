<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Single source of truth for every permission string in the system.
 * Reference this class from the seeder, policies, and middleware —
 * never use raw string literals.
 */
final class Permissions
{
    // Students
    const STUDENTS_VIEW   = 'students.view';
    const STUDENTS_CREATE = 'students.create';
    const STUDENTS_EDIT   = 'students.edit';
    const STUDENTS_DELETE = 'students.delete';

    // Staff
    const STAFF_VIEW   = 'staff.view';
    const STAFF_CREATE = 'staff.create';
    const STAFF_EDIT   = 'staff.edit';
    const STAFF_DELETE = 'staff.delete';

    // Academic
    const ACADEMIC_YEARS_MANAGE = 'academic-years.manage';
    const CLASSES_MANAGE        = 'classes.manage';
    const SECTIONS_MANAGE       = 'sections.manage';
    const SUBJECTS_MANAGE       = 'subjects.manage';
    const TIMETABLES_MANAGE     = 'timetables.manage';
    const TIMETABLES_VIEW       = 'timetables.view';

    // Attendance
    const ATTENDANCE_VIEW = 'attendance.view';
    const ATTENDANCE_MARK = 'attendance.mark';

    // Exams
    const EXAMS_VIEW    = 'exams.view';
    const EXAMS_MANAGE  = 'exams.manage';
    const EXAMS_PUBLISH = 'exams.publish';
    const MARKS_ENTRY   = 'marks.entry';
    const MARKS_VIEW    = 'marks.view';

    // Finance
    const INVOICES_VIEW   = 'invoices.view';
    const INVOICES_CREATE = 'invoices.create';
    const INVOICES_MANAGE = 'invoices.manage';
    const PAYMENTS_RECORD = 'payments.record';
    const FEES_MANAGE     = 'fees.manage';

    // Library
    const BOOKS_MANAGE      = 'books.manage';
    const BOOKS_VIEW        = 'books.view';
    const BOOK_ISSUES_MANAGE = 'book-issues.manage';
    const BOOK_ISSUES_VIEW   = 'book-issues.view';

    // Settings & Users
    const SETTINGS_MANAGE = 'settings.manage';
    const USERS_MANAGE    = 'users.manage';

    // Admissions
    const ADMISSIONS_VIEW   = 'admissions.view';
    const ADMISSIONS_MANAGE = 'admissions.manage';

    // Announcements
    const ANNOUNCEMENTS_VIEW   = 'announcements.view';
    const ANNOUNCEMENTS_CREATE = 'announcements.create';
    const ANNOUNCEMENTS_MANAGE = 'announcements.manage';

    // Messaging
    const MESSAGES_SEND = 'messages.send';
    const MESSAGES_VIEW = 'messages.view';

    // Homework
    const HOMEWORK_MANAGE = 'homework.manage';
    const HOMEWORK_SUBMIT = 'homework.submit';
    const HOMEWORK_GRADE  = 'homework.grade';

    // Transport
    const TRANSPORT_MANAGE = 'transport.manage';
    const TRANSPORT_VIEW   = 'transport.view';

    // Leave
    const LEAVES_VIEW   = 'leaves.view';
    const LEAVES_SUBMIT = 'leaves.submit';
    const LEAVES_MANAGE = 'leaves.manage';

    // Analytics & Reports
    const ANALYTICS_VIEW = 'analytics.view';
    const REPORTS_VIEW   = 'reports.view';

    // Term / Annual Consolidated Results
    const TERM_RESULTS_MANAGE  = 'term-results.manage';
    const TERM_RESULTS_PUBLISH = 'term-results.publish';

    // Documents: ID cards, transcripts, certificates
    const ID_CARDS_GENERATE  = 'id-cards.generate';
    const TRANSCRIPTS_VIEW   = 'transcripts.view';
    const CERTIFICATES_ISSUE = 'certificates.issue';

    // Year-end promotion & rollover (irreversible bulk operation — admin/principal only)
    const PROMOTION_MANAGE = 'promotion.manage';

    // Audit-log viewer — admin/principal only; never expose to lower roles
    const AUDIT_VIEW = 'audit.view';

    /**
     * Safe permission check for UI gating (nav links, quick-action buttons).
     * Spatie's User::can() throws PermissionDoesNotExist — a hard 500 — when
     * a permission row hasn't been provisioned yet (e.g. mid-deploy, before
     * the seeder has (re)run). A missing nav link is a cosmetic downgrade;
     * a 500 on every authenticated page is not. Treat "not provisioned" as
     * "no" here. This must NEVER be used as the actual authorization check —
     * controllers keep using $this->authorize()/Gate::authorize(), which
     * should stay loud about misconfiguration.
     */
    public static function userCan(User $user, string $permission): bool
    {
        try {
            return $user->can($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    /** Returns every permission string — used by the seeder and Gate::before. */
    public static function all(): array
    {
        return [
            self::STUDENTS_VIEW, self::STUDENTS_CREATE, self::STUDENTS_EDIT, self::STUDENTS_DELETE,
            self::STAFF_VIEW, self::STAFF_CREATE, self::STAFF_EDIT, self::STAFF_DELETE,
            self::ACADEMIC_YEARS_MANAGE, self::CLASSES_MANAGE, self::SECTIONS_MANAGE,
            self::SUBJECTS_MANAGE, self::TIMETABLES_MANAGE, self::TIMETABLES_VIEW,
            self::ATTENDANCE_VIEW, self::ATTENDANCE_MARK,
            self::EXAMS_VIEW, self::EXAMS_MANAGE, self::EXAMS_PUBLISH,
            self::MARKS_ENTRY, self::MARKS_VIEW,
            self::INVOICES_VIEW, self::INVOICES_CREATE, self::INVOICES_MANAGE,
            self::PAYMENTS_RECORD, self::FEES_MANAGE,
            self::BOOKS_MANAGE, self::BOOKS_VIEW, self::BOOK_ISSUES_MANAGE, self::BOOK_ISSUES_VIEW,
            self::SETTINGS_MANAGE, self::USERS_MANAGE,
            self::ADMISSIONS_VIEW, self::ADMISSIONS_MANAGE,
            self::ANNOUNCEMENTS_VIEW, self::ANNOUNCEMENTS_CREATE, self::ANNOUNCEMENTS_MANAGE,
            self::MESSAGES_SEND, self::MESSAGES_VIEW,
            self::HOMEWORK_MANAGE, self::HOMEWORK_SUBMIT, self::HOMEWORK_GRADE,
            self::TRANSPORT_MANAGE, self::TRANSPORT_VIEW,
            self::LEAVES_VIEW, self::LEAVES_SUBMIT, self::LEAVES_MANAGE,
            self::TERM_RESULTS_MANAGE, self::TERM_RESULTS_PUBLISH,
            self::ID_CARDS_GENERATE, self::TRANSCRIPTS_VIEW, self::CERTIFICATES_ISSUE,
            self::PROMOTION_MANAGE,
            self::AUDIT_VIEW,
            self::ANALYTICS_VIEW, self::REPORTS_VIEW,
        ];
    }
}
