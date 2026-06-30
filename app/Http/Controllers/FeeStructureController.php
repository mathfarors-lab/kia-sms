<?php

namespace App\Http\Controllers;

use App\Models\FeeStructure;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class FeeStructureController extends Controller
{
    public function index()
    {
        $this->authorize('fees.manage');
        $fees = FeeStructure::with('schoolClass')->latest()->paginate(25);
        return view('fee-structures.index', compact('fees'));
    }

    public function create()
    {
        $this->authorize('fees.manage');
        $classes = SchoolClass::orderBy('name')->get();
        return view('fee-structures.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $this->authorize('fees.manage');
        $data = $request->validate([
            'name'            => 'required|string|max:200',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'amount'          => 'required|numeric|min:0.01',
            'frequency'       => 'required|in:once,monthly,term,annual',
            'is_active'       => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        FeeStructure::create($data);
        return redirect()->route('fee-structures.index')->with('success', __('messages.created'));
    }

    public function edit(FeeStructure $feeStructure)
    {
        $this->authorize('fees.manage');
        $classes = SchoolClass::orderBy('name')->get();
        return view('fee-structures.edit', compact('feeStructure', 'classes'));
    }

    public function update(Request $request, FeeStructure $feeStructure)
    {
        $this->authorize('fees.manage');
        $data = $request->validate([
            'name'            => 'required|string|max:200',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'amount'          => 'required|numeric|min:0.01',
            'frequency'       => 'required|in:once,monthly,term,annual',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $feeStructure->update($data);
        return redirect()->route('fee-structures.index')->with('success', __('messages.updated'));
    }

    public function destroy(FeeStructure $feeStructure)
    {
        $this->authorize('fees.manage');
        $feeStructure->delete();
        return redirect()->route('fee-structures.index')->with('success', __('messages.deleted'));
    }
}
