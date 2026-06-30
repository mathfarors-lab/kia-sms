<?php

namespace App\Http\Controllers;

use App\Models\Scholarship;
use App\Models\Student;
use Illuminate\Http\Request;

class ScholarshipController extends Controller
{
    public function index()
    {
        $this->authorize('fees.manage');
        $scholarships = Scholarship::with('student')->latest()->paginate(25);
        return view('scholarships.index', compact('scholarships'));
    }

    public function create()
    {
        $this->authorize('fees.manage');
        $students = Student::orderBy('name_en')->get();
        return view('scholarships.create', compact('students'));
    }

    public function store(Request $request)
    {
        $this->authorize('fees.manage');
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type'       => 'required|in:percent,fixed',
            'value'      => 'required|numeric|min:0.01',
            'reason'     => 'nullable|string|max:500',
            'is_active'  => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        Scholarship::create($data);
        return redirect()->route('scholarships.index')->with('success', __('messages.created'));
    }

    public function edit(Scholarship $scholarship)
    {
        $this->authorize('fees.manage');
        $students = Student::orderBy('name_en')->get();
        return view('scholarships.edit', compact('scholarship', 'students'));
    }

    public function update(Request $request, Scholarship $scholarship)
    {
        $this->authorize('fees.manage');
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type'       => 'required|in:percent,fixed',
            'value'      => 'required|numeric|min:0.01',
            'reason'     => 'nullable|string|max:500',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $scholarship->update($data);
        return redirect()->route('scholarships.index')->with('success', __('messages.updated'));
    }

    public function destroy(Scholarship $scholarship)
    {
        $this->authorize('fees.manage');
        $scholarship->delete();
        return redirect()->route('scholarships.index')->with('success', __('messages.deleted'));
    }
}
