<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Staff;
use App\Models\Setting;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function redirect(Request $request)
    {
        return redirect()->route($request->user()->dashboardRoute());
    }

    public function admin()
    {
        $stats = [
            'total_students' => Student::count(),
            'total_staff'    => Staff::count(),
            'enrolled'       => Student::where('status', 'enrolled')->count(),
        ];
        return view('dashboard.admin', compact('stats'));
    }

    public function principal()
    {
        $stats = [
            'total_students' => Student::count(),
            'enrolled'       => Student::where('status', 'enrolled')->count(),
            'total_staff'    => Staff::count(),
        ];
        return view('dashboard.principal', compact('stats'));
    }

    public function teacher()
    {
        return view('dashboard.teacher');
    }

    public function accountant()
    {
        return view('dashboard.accountant');
    }

    public function librarian()
    {
        return view('dashboard.librarian');
    }

    public function receptionist()
    {
        $stats = [
            'total_students' => Student::count(),
            'enrolled'       => Student::where('status', 'enrolled')->count(),
        ];
        return view('dashboard.receptionist', compact('stats'));
    }

    public function student()
    {
        $student = auth()->user()->student;
        return view('dashboard.student', compact('student'));
    }

    public function parent()
    {
        $children = auth()->user()->wards;
        return view('dashboard.parent', compact('children'));
    }
}
