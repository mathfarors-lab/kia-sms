<?php

use App\Http\Controllers\AcademicAnalyticsController;
use App\Http\Controllers\AcademicCalendarController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BakongAdminController;
use App\Http\Controllers\BakongController;
use App\Http\Controllers\BillingStatementController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisciplineIncidentController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamMarkController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FeedbackDashboardController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\FinanceReportController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\GradeScaleController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\HomeworkController;
use App\Http\Controllers\IdCardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportCommentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScholarshipController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolDocumentController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffDevelopmentLogController;
use App\Http\Controllers\StaffDocumentController;
use App\Http\Controllers\StaffEvaluationController;
use App\Http\Controllers\StaffQualificationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDocumentController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\StudentTransferController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyResponseController;
use App\Http\Controllers\SurveyResultController;
use App\Http\Controllers\TermResultController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TranscriptController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorLogController;
use App\Http\Middleware\VerifyCsrfToken;
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

    // Each role-specific dashboard redirects a wrong-role visitor to their
    // own dashboard rather than rendering (own-dashboard:ROLE — see
    // EnsureOwnDashboard). Owner's dashboard is separately guarded by
    // role:owner in its own route group below.
    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->name('dashboard.admin')->middleware('own-dashboard:admin');
    Route::get('/dashboard/principal', [DashboardController::class, 'principal'])->name('dashboard.principal')->middleware('own-dashboard:principal');
    Route::get('/dashboard/teacher', [DashboardController::class, 'teacher'])->name('dashboard.teacher')->middleware('own-dashboard:teacher');
    Route::get('/dashboard/accountant', [DashboardController::class, 'accountant'])->name('dashboard.accountant')->middleware('own-dashboard:accountant');
    Route::get('/dashboard/librarian', [DashboardController::class, 'librarian'])->name('dashboard.librarian')->middleware('own-dashboard:librarian');
    Route::get('/dashboard/receptionist', [DashboardController::class, 'receptionist'])->name('dashboard.receptionist')->middleware('own-dashboard:receptionist');
    Route::get('/dashboard/student', [DashboardController::class, 'student'])->name('dashboard.student')->middleware('own-dashboard:student');
    Route::get('/dashboard/parent', [DashboardController::class, 'parent'])->name('dashboard.parent')->middleware('own-dashboard:parent');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Students
    Route::resource('students', StudentController::class);
    Route::get('/students/export/excel', [StudentController::class, 'exportExcel'])->name('students.export-excel');
    Route::get('/students/export/pdf', [StudentController::class, 'exportPdf'])->name('students.export-pdf');
    Route::post('/students/{student}/documents', [StudentDocumentController::class, 'store'])->name('student-documents.store');
    Route::delete('/student-documents/{document}', [StudentDocumentController::class, 'destroy'])->name('student-documents.destroy');
    Route::get('/student-documents/{document}/download', [StudentDocumentController::class, 'download'])->name('student-documents.download');

    // Discipline incidents (G5) — shared route, internal role/ownership branching per action.
    Route::get('/students/{student}/discipline-incidents', [DisciplineIncidentController::class, 'index'])->name('discipline-incidents.index');
    Route::get('/students/{student}/discipline-incidents/create', [DisciplineIncidentController::class, 'create'])->name('discipline-incidents.create');
    Route::post('/students/{student}/discipline-incidents', [DisciplineIncidentController::class, 'store'])->name('discipline-incidents.store');
    Route::get('/discipline-incidents/{incident}/edit', [DisciplineIncidentController::class, 'edit'])->name('discipline-incidents.edit');
    Route::put('/discipline-incidents/{incident}', [DisciplineIncidentController::class, 'update'])->name('discipline-incidents.update');

    // Guided transfer / withdrawal — wraps StudentService::update() so the
    // existing leaving-certificate auto-issuance fires exactly as it does
    // for a direct status edit.
    Route::get('/students/{student}/transfer', [StudentTransferController::class, 'transferForm'])->name('students.transfer.form');
    Route::post('/students/{student}/transfer', [StudentTransferController::class, 'transfer'])->name('students.transfer');
    Route::get('/students/{student}/withdraw', [StudentTransferController::class, 'withdrawForm'])->name('students.withdraw.form');
    Route::post('/students/{student}/withdraw', [StudentTransferController::class, 'withdraw'])->name('students.withdraw');

    // Staff
    Route::resource('staff', StaffController::class);
    Route::get('/staff/export/excel', [StaffController::class, 'exportExcel'])->name('staff.export-excel');
    Route::get('/staff/export/pdf', [StaffController::class, 'exportPdf'])->name('staff.export-pdf');
    Route::post('/staff/{staff}/qualifications', [StaffQualificationController::class, 'store'])->name('staff-qualifications.store');
    Route::delete('/staff-qualifications/{qualification}', [StaffQualificationController::class, 'destroy'])->name('staff-qualifications.destroy');
    Route::post('/staff/{staff}/documents', [StaffDocumentController::class, 'store'])->name('staff-documents.store');
    Route::delete('/staff-documents/{document}', [StaffDocumentController::class, 'destroy'])->name('staff-documents.destroy');
    Route::get('/staff-documents/{document}/download', [StaffDocumentController::class, 'download'])->name('staff-documents.download');

    // Staff evaluations — deliberately NOT under staff.view (excludes teacher
    // today); index()/show() carve out self-access to finalized rows only.
    Route::get('/staff/{staff}/evaluations', [StaffEvaluationController::class, 'index'])->name('staff-evaluations.index');
    Route::get('/staff/{staff}/evaluations/create', [StaffEvaluationController::class, 'create'])->name('staff-evaluations.create');
    Route::post('/staff/{staff}/evaluations', [StaffEvaluationController::class, 'store'])->name('staff-evaluations.store');
    Route::get('/staff-evaluations/{evaluation}', [StaffEvaluationController::class, 'show'])->name('staff-evaluations.show');
    Route::get('/staff-evaluations/{evaluation}/edit', [StaffEvaluationController::class, 'edit'])->name('staff-evaluations.edit');
    Route::put('/staff-evaluations/{evaluation}', [StaffEvaluationController::class, 'update'])->name('staff-evaluations.update');
    Route::post('/staff-evaluations/{evaluation}/finalize', [StaffEvaluationController::class, 'finalize'])->name('staff-evaluations.finalize');

    // Staff development / CPD log — same audience as qualifications (G1).
    Route::post('/staff/{staff}/development-logs', [StaffDevelopmentLogController::class, 'store'])->name('staff-development-logs.store');
    Route::delete('/staff-development-logs/{log}', [StaffDevelopmentLogController::class, 'destroy'])->name('staff-development-logs.destroy');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Academic Years
    Route::resource('academic-years', AcademicYearController::class);
    Route::post('/academic-years/{academicYear}/semesters', [SemesterController::class, 'store'])->name('semesters.store');
    Route::delete('/semesters/{semester}', [SemesterController::class, 'destroy'])->name('semesters.destroy');

    // Classes & Sections
    Route::resource('classes', SchoolClassController::class);
    Route::resource('classes.sections', SectionController::class)->shallow();

    // Subjects
    Route::resource('subjects', SubjectController::class);

    // Timetable
    Route::get('/timetables', [TimetableController::class, 'picker'])->name('timetables.index');
    Route::get('/staff/{staff}/teaching-schedule', [TimetableController::class, 'teacherSchedule'])->name('staff.teaching-schedule');
    Route::get('/sections/{section}/timetable', [TimetableController::class, 'index'])->name('timetable.show');
    Route::post('/sections/{section}/timetable', [TimetableController::class, 'store'])->name('timetable.store');
    Route::delete('/timetable/{timetable}', [TimetableController::class, 'destroy'])->name('timetable.destroy');

    // Attendance
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/sections/{section}/attendance', [AttendanceController::class, 'markForm'])->name('attendance.mark');
    Route::post('/sections/{section}/attendance', [AttendanceController::class, 'mark'])->name('attendance.store');

    // Gate scan station (M3) — kiosk page + the API it polls/posts to.
    Route::get('/gate', [GateController::class, 'station'])->name('gate.station');
    Route::post('/gate/scan', [GateController::class, 'scan'])->name('gate.scan');
    Route::get('/gate/arrivals-feed', [GateController::class, 'arrivalsFeed'])->name('gate.arrivals-feed');

    // Visitor log (M4) — same front-desk audience as the gate station.
    Route::get('/visitors', [VisitorLogController::class, 'index'])->name('visitors.index');
    Route::post('/visitors', [VisitorLogController::class, 'store'])->name('visitors.store');
    Route::post('/visitors/{visitor}/check-out', [VisitorLogController::class, 'checkOut'])->name('visitors.check-out');

    // Grade Scales
    Route::resource('grade-scales', GradeScaleController::class)->except(['show']);

    // Exams
    Route::resource('exams', ExamController::class)->except(['show']);
    Route::post('/exams/{exam}/publish', [ExamController::class, 'publish'])->name('exams.publish');

    // Curriculum (G3) — syllabus content per class-subject.
    Route::get('/curriculum', [CurriculumController::class, 'index'])->name('curriculum.index');
    Route::get('/classes/{class}/curriculum', [CurriculumController::class, 'forClass'])->name('curriculum.for-class');
    Route::get('/class-subjects/{classSubject}/curriculum', [CurriculumController::class, 'show'])->name('curriculum.show');
    Route::post('/class-subjects/{classSubject}/curriculum-topics', [CurriculumController::class, 'store'])->name('curriculum-topics.store');
    Route::get('/curriculum-topics/{topic}/edit', [CurriculumController::class, 'edit'])->name('curriculum-topics.edit');
    Route::put('/curriculum-topics/{topic}', [CurriculumController::class, 'update'])->name('curriculum-topics.update');
    Route::post('/curriculum-topics/{topic}/toggle', [CurriculumController::class, 'toggle'])->name('curriculum-topics.toggle');
    Route::delete('/curriculum-topics/{topic}', [CurriculumController::class, 'destroy'])->name('curriculum-topics.destroy');

    // Academic Calendar (G3) — read-only view, open to any authenticated user.
    Route::get('/academic-calendar', [AcademicCalendarController::class, 'index'])->name('academic-calendar.index');
    Route::resource('holidays', HolidayController::class)->except(['show']);

    // General-purpose document repository (G4) — school-wide policies/forms/
    // templates, distinct from every per-person document already in the app.
    Route::get('/school-documents', [SchoolDocumentController::class, 'index'])->name('school-documents.index');
    Route::get('/school-documents/create', [SchoolDocumentController::class, 'create'])->name('school-documents.create');
    Route::post('/school-documents', [SchoolDocumentController::class, 'store'])->name('school-documents.store');
    Route::delete('/school-documents/{document}', [SchoolDocumentController::class, 'destroy'])->name('school-documents.destroy');
    Route::get('/school-documents/{document}/download', [SchoolDocumentController::class, 'download'])->name('school-documents.download');

    // Exam Marks
    Route::get('/exam-marks', [ExamMarkController::class, 'index'])->name('exam-marks.index');
    Route::get('/exams/{exam}/sections/{section}/marks', [ExamMarkController::class, 'grid'])->name('exam-marks.grid');
    Route::post('/exams/{exam}/sections/{section}/marks', [ExamMarkController::class, 'save'])->name('exam-marks.save');
    Route::get('/exams/{exam}/sections/{section}/marks/export/excel', [ExamMarkController::class, 'exportExcel'])->name('exam-marks.export-excel');
    Route::get('/exams/{exam}/sections/{section}/marks/export/pdf', [ExamMarkController::class, 'exportPdf'])->name('exam-marks.export-pdf');

    // Per-exam report cards
    Route::get('/exams/{exam}/students/{student}/report-card', [ReportCardController::class, 'show'])->name('report-card.show');
    Route::get('/exams/{exam}/students/{student}/report-card/pdf', [ReportCardController::class, 'pdf'])->name('report-card.pdf');

    // Consolidated term / annual report cards
    Route::prefix('term-results')->name('term-results.')->group(function () {
        Route::get('/', [TermResultController::class, 'index'])->name('index');
        Route::post('/compute', [TermResultController::class, 'compute'])->name('compute');
        Route::post('/{academicYear}/finalize', [TermResultController::class, 'finalize'])->name('finalize');
        Route::post('/{academicYear}/publish', [TermResultController::class, 'publish'])->name('publish');
        Route::get('/{academicYear}/{semesterSlug}/{student}', [TermResultController::class, 'show'])->name('show');
        Route::get('/{academicYear}/{semesterSlug}/{student}/pdf', [TermResultController::class, 'pdf'])->name('pdf');
        Route::get('/{academicYear}/{semesterSlug}/{student}/remark', [TermResultController::class, 'editRemark'])->name('remark.edit');
        Route::patch('/{academicYear}/{semesterSlug}/{student}/remark', [TermResultController::class, 'updateRemark'])->name('remark.update');
    });

    // Report-card comment bank (M4)
    Route::prefix('report-comments')->name('report-comments.')->group(function () {
        Route::get('/', [ReportCommentController::class, 'index'])->name('index');
        Route::post('/', [ReportCommentController::class, 'store'])->name('store');
        Route::patch('/{comment}', [ReportCommentController::class, 'update'])->name('update');
        Route::delete('/{comment}', [ReportCommentController::class, 'destroy'])->name('destroy');
    });

    // ID Cards
    Route::prefix('id-cards')->name('id-cards.')->group(function () {
        Route::get('/student/{student}', [IdCardController::class, 'showStudent'])->name('student.show');
        Route::get('/student/{student}/pdf', [IdCardController::class, 'pdfStudent'])->name('student.pdf');
        Route::get('/staff/{staff}', [IdCardController::class, 'showStaff'])->name('staff.show');
        Route::get('/staff/{staff}/pdf', [IdCardController::class, 'pdfStaff'])->name('staff.pdf');
        Route::get('/section/{section}/batch', [IdCardController::class, 'batchPreview'])->name('batch.preview');
        Route::get('/section/{section}/batch/pdf', [IdCardController::class, 'batchPdf'])->name('batch.pdf');
    });

    // Transcripts
    Route::prefix('transcripts')->name('transcripts.')->group(function () {
        Route::get('/student/{student}', [TranscriptController::class, 'show'])->name('show');
        Route::get('/student/{student}/pdf', [TranscriptController::class, 'pdf'])->name('pdf');
    });

    // Student billing statement — full invoice/payment ledger with running balance
    Route::prefix('billing-statement')->name('billing-statement.')->group(function () {
        Route::get('/student/{student}', [BillingStatementController::class, 'show'])->name('show');
        Route::get('/student/{student}/pdf', [BillingStatementController::class, 'pdf'])->name('pdf');
    });

    // Certificates (admin/principal only)
    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/student/{student}/enrollment', [CertificateController::class, 'enrollment'])->name('enrollment');
        Route::get('/student/{student}/enrollment/pdf', [CertificateController::class, 'enrollmentPdf'])->name('enrollment.pdf');
        Route::get('/student/{student}/leaving', [CertificateController::class, 'leaving'])->name('leaving');
        Route::get('/student/{student}/leaving/pdf', [CertificateController::class, 'leavingPdf'])->name('leaving.pdf');
        Route::get('/student/{student}/graduation', [CertificateController::class, 'graduation'])->name('graduation');
        Route::get('/student/{student}/graduation/pdf', [CertificateController::class, 'graduationPdf'])->name('graduation.pdf');
    });

    // Finance — Fee Structures & Scholarships
    Route::resource('fee-structures', FeeStructureController::class)->except(['show']);
    Route::resource('scholarships', ScholarshipController::class)->except(['show']);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/export/excel', [InvoiceController::class, 'exportExcel'])->name('invoices.export-excel');
    Route::get('/invoices/export/pdf', [InvoiceController::class, 'exportPdf'])->name('invoices.export-pdf');
    Route::get('/invoices/generate', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices/generate', [InvoiceController::class, 'generate'])->name('invoices.generate');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    // KHQR intent regeneration (for expired QR codes)
    Route::post('/invoices/{invoice}/khqr/regenerate', [InvoiceController::class, 'regenerateKhqr'])
        ->name('invoices.khqr.regenerate');

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

    // Surveys — proactive/structured, distinct from Feedback's reactive/open-ended flow
    Route::get('/my-surveys', [SurveyResponseController::class, 'index'])->name('surveys.my');
    Route::resource('surveys', SurveyController::class);
    Route::post('/surveys/{survey}/publish', [SurveyController::class, 'publish'])->name('surveys.publish');
    Route::post('/surveys/{survey}/close', [SurveyController::class, 'close'])->name('surveys.close');
    Route::get('/surveys/{survey}/take', [SurveyResponseController::class, 'create'])->name('surveys.take');
    Route::post('/surveys/{survey}/take', [SurveyResponseController::class, 'store'])->name('surveys.submit');
    Route::get('/surveys/{survey}/results', [SurveyResultController::class, 'show'])->name('surveys.results');
    Route::get('/surveys/{survey}/results/export/excel', [SurveyResultController::class, 'exportExcel'])->name('surveys.export-excel');
    Route::get('/surveys/{survey}/results/export/pdf', [SurveyResultController::class, 'exportPdf'])->name('surveys.export-pdf');

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
    Route::get('/book-issues/overdue', [BookController::class, 'overdueIssues'])->name('book-issues.overdue');

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

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/academic-analytics', [AcademicAnalyticsController::class, 'index'])->name('academic-analytics.index');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/enrollment', [ReportController::class, 'enrollmentRoster'])->name('reports.enrollment');
    Route::get('/reports/attendance', [ReportController::class, 'attendanceSummary'])->name('reports.attendance');
    Route::get('/reports/fees', [ReportController::class, 'feeCollection'])->name('reports.fees');
    Route::get('/reports/staff-punctuality', [ReportController::class, 'staffPunctuality'])->name('reports.staff-punctuality');

    // Leave
    Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/leaves/request', [LeaveController::class, 'create'])->name('leaves.create');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');

    // Owner-only: branch switcher (M1) + consolidated dashboard and branch
    // management (M2). role:owner (not a Permissions constant — this is an
    // architectural tier, not a grantable permission) returns 403 for
    // everyone else via Spatie's RoleMiddleware.
    Route::post('/branch/switch', [BranchController::class, 'switch'])->name('branch.switch');

    Route::middleware('role:owner')->prefix('owner')->name('owner.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'owner'])->name('dashboard');

        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('/branches/new', [BranchController::class, 'create'])->name('branches.create');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
        Route::patch('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::post('/branches/{branch}/toggle-active', [BranchController::class, 'toggleActive'])->name('branches.toggle-active');

        Route::get('/branches/{branch}/admins', [BranchController::class, 'admins'])->name('branches.admins');
        Route::post('/branches/{branch}/admins', [BranchController::class, 'appointAdmin'])->name('branches.admins.appoint');
        Route::delete('/branches/{branch}/admins/{user}', [BranchController::class, 'removeAdmin'])->name('branches.admins.remove');
    });

    // Branch logo download — gated by branches.view-equivalent (owner-only
    // for now; not under the prefix group so it stays a simple GET path).
    Route::get('/branches/{branch}/logo', [BranchController::class, 'logo'])
        ->middleware('role:owner')->name('branches.logo');

    // Admissions pipeline (receptionist/principal/admin)
    Route::prefix('admissions')->name('admissions.')->group(function () {
        Route::get('/', [AdmissionController::class, 'index'])->name('index');
        Route::get('/export/excel', [AdmissionController::class, 'exportExcel'])->name('export-excel');
        Route::get('/export/pdf', [AdmissionController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/new', [AdmissionController::class, 'create'])->name('create');
        Route::post('/', [AdmissionController::class, 'store'])->name('store');
        Route::get('/{admission}', [AdmissionController::class, 'show'])->name('show');
        Route::get('/{admission}/edit', [AdmissionController::class, 'edit'])->name('edit');
        Route::patch('/{admission}', [AdmissionController::class, 'update'])->name('update');
        Route::post('/{admission}/status', [AdmissionController::class, 'updateStatus'])->name('status');
        Route::post('/{admission}/convert', [AdmissionController::class, 'convert'])->name('convert');
        Route::get('/{admission}/document', [AdmissionController::class, 'document'])->name('document');
    });

    // Year-end promotion & rollover (admin/principal only)
    Route::prefix('promotion')->name('promotion.')->group(function () {
        Route::get('/', [PromotionController::class, 'index'])->name('index');
        Route::post('/preview', [PromotionController::class, 'preview'])->name('preview');
        Route::post('/execute', [PromotionController::class, 'execute'])->name('execute');
    });

    // Gated photo routes — private disk, authorized users only
    Route::get('/students/{student}/photo', [PhotoController::class, 'student'])->name('students.photo');
    Route::get('/staff/{staff}/photo', [PhotoController::class, 'staff'])->name('staff.photo');

    // In-app notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/{id}/read-go', [NotificationController::class, 'readAndGo'])->name('read-go');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
    });

    // Audit-log viewer (admin/principal only)
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    // Admin — Bakong audit log + replay
    Route::get('/admin/bakong/failed', [BakongAdminController::class, 'failedList'])
        ->name('admin.bakong.failed');
    Route::post('/admin/bakong/failed/{verification}/replay', [BakongAdminController::class, 'replay'])
        ->name('admin.bakong.replay');

    // User management (admin only — USERS_MANAGE permission)
    Route::resource('users', UserController::class)->except(['show']);
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    // Parent portal
    Route::get('/parent/children', [ParentPortalController::class, 'children'])->name('parent.children');
    Route::get('/parent/children/{student}', [ParentPortalController::class, 'childDetail'])->name('parent.child.show');

    // Student self-service portal
    Route::get('/student/attendance', [StudentPortalController::class, 'attendance'])->name('student.attendance');

    // Feedback & Complaints — parent/student submission, staff inbox, satisfaction dashboard
    Route::prefix('feedback')->name('feedback.')->group(function () {
        Route::get('/', [FeedbackController::class, 'index'])->name('index');
        Route::get('/new', [FeedbackController::class, 'create'])->name('create');
        Route::post('/', [FeedbackController::class, 'store'])->name('store');
        Route::get('/dashboard', [FeedbackDashboardController::class, 'index'])->name('dashboard');
        Route::get('/{feedback}', [FeedbackController::class, 'show'])->name('show');
        Route::post('/{feedback}/reply', [FeedbackController::class, 'reply'])->name('reply');
        Route::post('/{feedback}/assign', [FeedbackController::class, 'assign'])->name('assign');
        Route::post('/{feedback}/status', [FeedbackController::class, 'updateStatus'])->name('status');
        Route::post('/{feedback}/reopen', [FeedbackController::class, 'reopen'])->name('reopen');
        Route::get('/{feedback}/attachment', [FeedbackController::class, 'downloadAttachment'])->name('attachment');
    });
});

// Bakong KHQR webhook (push model — bank/PSP integration).
// The controller checks BAKONG_DISABLE_WEBHOOK and returns 404 when disabled.
// Default: disabled (polling model is active). Set BAKONG_DISABLE_WEBHOOK=false
// ONLY if your acquiring bank provides a real PUSH webhook AND you are NOT using polling.
// Never run both as live payment paths simultaneously.
Route::post('/webhooks/bakong', [BakongController::class, 'webhook'])
    ->name('webhooks.bakong')
    ->withoutMiddleware([VerifyCsrfToken::class]);

require __DIR__.'/auth.php';
