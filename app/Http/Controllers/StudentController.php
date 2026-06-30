<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\StudentService;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(private StudentService $service) {}

    public function index(Request $request)
    {
        $this->authorize('students.view');

        $query = Student::query()->with('user');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_km', 'like', "%{$search}%")
                  ->orWhere('student_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        $students = $query->latest()->paginate(20)->withQueryString();

        return view('students.index', compact('students'));
    }

    public function create()
    {
        $this->authorize('students.create');
        return view('students.create');
    }

    public function store(StoreStudentRequest $request)
    {
        $student = $this->service->store(
            $request->safe()->except('photo'),
            $request->file('photo')
        );

        return redirect()->route('students.show', $student)
                         ->with('success', __('Student created successfully.'));
    }

    public function show(Student $student)
    {
        $this->authorize('students.view');
        $student->load('guardians');
        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $this->authorize('students.edit');
        return view('students.edit', compact('student'));
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $this->service->update(
            $student,
            $request->safe()->except('photo'),
            $request->file('photo')
        );

        return redirect()->route('students.show', $student)
                         ->with('success', __('Student updated successfully.'));
    }

    public function destroy(Student $student)
    {
        $this->authorize('students.delete');
        $this->service->destroy($student);
        return redirect()->route('students.index')
                         ->with('success', __('Student deleted.'));
    }
}
