<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\GradeScaleController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamMarkController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\ScholarshipController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\FinanceReportController;
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

    // Remaining placeholders
    Route::get('/books',          fn() => view('placeholder', ['title' => 'Library']))->name('books.index');
    Route::get('/users',          fn() => view('placeholder', ['title' => 'Users']))->name('users.index');

    // Parent & student portal placeholders
    Route::get('/parent/children',    fn() => view('placeholder', ['title' => 'My Children']))->name('parent.children');
    Route::get('/student/attendance', fn() => view('placeholder', ['title' => 'My Attendance']))->name('student.attendance');
});

require __DIR__.'/auth.php';
