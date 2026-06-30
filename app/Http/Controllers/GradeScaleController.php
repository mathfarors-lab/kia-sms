<?php

namespace App\Http\Controllers;

use App\Models\GradeScale;
use Illuminate\Http\Request;

class GradeScaleController extends Controller
{
    public function index()
    {
        $this->authorize('settings.manage');
        $scales = GradeScale::orderByDesc('min_score')->get();
        return view('grade-scales.index', compact('scales'));
    }

    public function create()
    {
        $this->authorize('settings.manage');
        return view('grade-scales.create');
    }

    public function store(Request $request)
    {
        $this->authorize('settings.manage');
        $data = $request->validate([
            'grade'      => 'required|string|max:5|unique:grade_scales',
            'min_score'  => 'required|numeric|min:0|max:100',
            'max_score'  => 'required|numeric|min:0|max:100|gte:min_score',
            'gpa'        => 'required|numeric|min:0|max:4',
            'remark_en'  => 'required|string|max:100',
            'remark_km'  => 'required|string|max:100',
        ]);

        GradeScale::create($data);
        return redirect()->route('grade-scales.index')->with('success', __('messages.created'));
    }

    public function edit(GradeScale $gradeScale)
    {
        $this->authorize('settings.manage');
        return view('grade-scales.edit', compact('gradeScale'));
    }

    public function update(Request $request, GradeScale $gradeScale)
    {
        $this->authorize('settings.manage');
        $data = $request->validate([
            'grade'      => 'required|string|max:5|unique:grade_scales,grade,' . $gradeScale->id,
            'min_score'  => 'required|numeric|min:0|max:100',
            'max_score'  => 'required|numeric|min:0|max:100|gte:min_score',
            'gpa'        => 'required|numeric|min:0|max:4',
            'remark_en'  => 'required|string|max:100',
            'remark_km'  => 'required|string|max:100',
        ]);

        $gradeScale->update($data);
        return redirect()->route('grade-scales.index')->with('success', __('messages.updated'));
    }

    public function destroy(GradeScale $gradeScale)
    {
        $this->authorize('settings.manage');
        $gradeScale->delete();
        return redirect()->route('grade-scales.index')->with('success', __('messages.deleted'));
    }
}
