<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\FinanceReportController;
use App\Http\Controllers\HomeworkController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ScholarshipController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\GradeScaleController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamMarkController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// Home → redirect to login or dashboard
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->dashboardRoute())
        : redirect()->route('login');
});

// Locale switcher
Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // Dashboard (role-aware redirect)
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');
    Route::get('/dashboard/admin',        [DashboardController::class, 'admin'])->name('dashboard.admin');
    Route::get('/dashboard/principal',    [DashboardController::class, 'principal'])->name('dashboard.principal');
    Route::get('/dashboard/teacher',      [DashboardController::class, 'teacher'])->name('dashboard.teacher');
    Route::get('/dashboard/accountant',   [DashboardController::class, 'accountant'])->name('dashboard.accountant');
    Route::get('/dashboard/librarian',    [DashboardController::class, 'librarian'])->name('dashboard.librarian');
    Route::get('/dashboard/receptionist', [DashboardController::class, 'receptionist'])->name('dashboard.receptionist');
    Route::get('/dashboard/student',      [DashboardController::class, 'student'])->name('dashboard.student');
    Route::get('/dashboard/parent',       [DashboardController::class, 'parent'])->name('dashboard.parent');

    // Profile
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Students
    Route::resource('students', StudentController::class);

    // Staff
    Route::resource('staff', StaffController::class);

    // Settings
    Route::get('/settings',   [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings',  [SettingController::class, 'update'])->name('settings.update');

    // Academic Years
    Route::resource('academic-years', AcademicYearController::class);

    // Classes & Sections
    Route::resource('classes', SchoolClassController::class);
    Route::resource('classes.sections', SectionController::class)->shallow();

    // Subjects
    Route::resource('subjects', SubjectController::class);

    // Timetable
    Route::get('/sections/{section}/timetable', [TimetableController::class, 'index'])->name('timetable.show');
    Route::post('/sections/{section}/timetable', [TimetableController::class, 'store'])->name('timetable.store');
    Route::delete('/timetable/{timetable}', [TimetableController::class, 'destroy'])->name('timetable.destroy');

    // Attendance
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/sections/{section}/attendance', [AttendanceController::class, 'markForm'])->name('attendance.mark');
    Route::post('/sections/{section}/attendance', [AttendanceController::class, 'mark'])->name('attendance.store');

    // Grade Scales
    Route::resource('grade-scales', GradeScaleController::class)->except(['show']);

    // Exams
    Route::resource('exams', ExamController::class)->except(['show']);
    Route::post('/exams/{exam}/publish', [ExamController::class, 'publish'])->name('exams.publish');

    // Exam Marks
    Route::get('/exam-marks', [ExamMarkController::class, 'index'])->name('exam-marks.index');
    Route::get('/exams/{exam}/sections/{section}/marks', [ExamMarkController::class, 'grid'])->name('exam-marks.grid');
    Route::post('/exams/{exam}/sections/{section}/marks', [ExamMarkController::class, 'save'])->name('exam-marks.save');

    // Report Cards
    Route::get('/exams/{exam}/students/{student}/report-card', [ReportCardController::class, 'show'])->name('report-card.show');
    Route::get('/exams/{exam}/students/{student}/report-card/pdf', [ReportCardController::class, 'pdf'])->name('report-card.pdf');

    // Finance — Fee Structures & Scholarships
    Route::resource('fee-structures', FeeStructureController::class)->except(['show']);
    Route::resource('scholarships', ScholarshipController::class)->except(['show']);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/generate', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices/generate', [InvoiceController::class, 'generate'])->name('invoices.generate');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    // Payments
    Route::get('/invoices/{invoice}/pay', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/invoices/{invoice}/pay', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::get('/payments/{payment}/receipt/pdf', [PaymentController::class, 'receiptPdf'])->name('payments.receipt-pdf');

    // Finance reports
    Route::get('/finance', [FinanceReportController::class, 'dashboard'])->name('finance.dashboard');
    Route::get('/finance/report', [FinanceReportController::class, 'report'])->name('finance.report');
    Route::get('/finance/export/excel', [FinanceReportController::class, 'exportExcel'])->name('finance.export-excel');
    Route::get('/finance/export/pdf', [FinanceReportController::class, 'exportPdf'])->name('finance.export-pdf');

    // ── Phase 5: Engagement ────────────────────────────────────────────────────

    // Announcements
    Route::resource('announcements', AnnouncementController::class);
    Route::post('/announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])
        ->name('announcements.publish');

    // Messaging
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/new', [ConversationController::class, 'create'])->name('conversations.create');
    Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/reply', [ConversationController::class, 'reply'])->name('conversations.reply');

    // Homework
    Route::get('/homework', [HomeworkController::class, 'index'])->name('homework.index');
    Route::get('/homework/new', [HomeworkController::class, 'create'])->name('homework.create');
    Route::post('/homework', [HomeworkController::class, 'store'])->name('homework.store');
    Route::get('/homework/{homework}', [HomeworkController::class, 'show'])->name('homework.show');
    Route::post('/homework/{homework}/submit', [HomeworkController::class, 'submit'])->name('homework.submit');
    Route::get('/homework/{homework}/download', [HomeworkController::class, 'download'])->name('homework.download');
    Route::post('/homework-submissions/{submission}/grade', [HomeworkController::class, 'grade'])->name('homework-submissions.grade');

    // Library
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/new', [BookController::class, 'create'])->name('books.create');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::patch('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    Route::get('/books/{book}/issue', [BookController::class, 'issueForm'])->name('books.issue');
    Route::post('/books/{book}/issue', [BookController::class, 'issue'])->name('books.issue.store');
    Route::post('/book-issues/{issue}/return', [BookController::class, 'returnBook'])->name('book-issues.return');

    // Transport
    Route::get('/transport/routes', [TransportController::class, 'routesIndex'])->name('transport.routes.index');
    Route::get('/transport/routes/new', [TransportController::class, 'routesCreate'])->name('transport.routes.create');
    Route::post('/transport/routes', [TransportController::class, 'routesStore'])->name('transport.routes.store');
    Route::get('/transport/routes/{route}/edit', [TransportController::class, 'routesEdit'])->name('transport.routes.edit');
    Route::patch('/transport/routes/{route}', [TransportController::class, 'routesUpdate'])->name('transport.routes.update');
    Route::get('/transport/routes/{route}/vehicles/new', [TransportController::class, 'vehiclesCreate'])->name('transport.vehicles.create');
    Route::post('/transport/routes/{route}/vehicles', [TransportController::class, 'vehiclesStore'])->name('transport.vehicles.store');
    Route::get('/transport/students', [TransportController::class, 'studentsIndex'])->name('transport.students');
    Route::post('/transport/students', [TransportController::class, 'studentsAssign'])->name('transport.students.assign');
    Route::delete('/transport/students/{student}', [TransportController::class, 'studentsRemove'])->name('transport.students.remove');

    // Users management placeholder
    Route::get('/users', fn() => view('placeholder', ['title' => 'Users']))->name('users.index');

    // Parent & student portal placeholders
    Route::get('/parent/children',    fn() => view('placeholder', ['title' => 'My Children']))->name('parent.children');
    Route::get('/student/attendance', fn() => view('placeholder', ['title' => 'My Attendance']))->name('student.attendance');
});

require __DIR__.'/auth.php';
