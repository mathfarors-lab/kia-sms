<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subject\StoreSubjectRequest;
use App\Http\Requests\Subject\UpdateSubjectRequest;
use App\Models\Subject;

class SubjectController extends Controller
{
    public function index()
    {
        $this->authorize('subjects.manage');
        $subjects = Subject::withCount('schoolClasses')->latest()->paginate(20);
        return view('subjects.index', compact('subjects'));
    }

    public function create()
    {
        $this->authorize('subjects.manage');
        return view('subjects.create');
    }

    public function store(StoreSubjectRequest $request)
    {
        $this->authorize('subjects.manage');
        Subject::create($request->validated());
        return redirect()->route('subjects.index')->with('success', 'Subject created.');
    }

    public function edit(Subject $subject)
    {
        $this->authorize('subjects.manage');
        return view('subjects.edit', compact('subject'));
    }

    public function update(UpdateSubjectRequest $request, Subject $subject)
    {
        $this->authorize('subjects.manage');
        $subject->update($request->validated());
        return redirect()->route('subjects.index')->with('success', 'Subject updated.');
    }

    public function destroy(Subject $subject)
    {
        $this->authorize('subjects.manage');
        $subject->delete();
        return redirect()->route('subjects.index')->with('success', 'Subject deleted.');
    }
}
