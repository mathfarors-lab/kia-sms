<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LocaleController;
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

    // Placeholder routes for sidebar links (Phase 2+)
    Route::get('/academic-years', fn() => view('placeholder', ['title' => 'Academic Years']))->name('academic-years.index');
    Route::get('/classes',        fn() => view('placeholder', ['title' => 'Classes']))->name('classes.index');
    Route::get('/attendance',     fn() => view('placeholder', ['title' => 'Attendance']))->name('attendance.index');
    Route::get('/invoices',       fn() => view('placeholder', ['title' => 'Invoices']))->name('invoices.index');
    Route::get('/fee-structures', fn() => view('placeholder', ['title' => 'Fee Structures']))->name('fee-structures.index');
    Route::get('/books',          fn() => view('placeholder', ['title' => 'Library']))->name('books.index');
    Route::get('/users',          fn() => view('placeholder', ['title' => 'Users']))->name('users.index');

    // Parent & student portal placeholders
    Route::get('/parent/children',    fn() => view('placeholder', ['title' => 'My Children']))->name('parent.children');
    Route::get('/student/attendance', fn() => view('placeholder', ['title' => 'My Attendance']))->name('student.attendance');
});

require __DIR__.'/auth.php';
