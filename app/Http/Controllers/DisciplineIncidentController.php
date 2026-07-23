<?php

namespace App\Http\Controllers;

use App\Http\Requests\Discipline\StoreDisciplineIncidentRequest;
use App\Models\DisciplineIncident;
use App\Models\Student;
use App\Models\User;
use App\Notifications\DisciplineIncidentLogged;
use App\Support\Permissions as P;
use Illuminate\Support\Facades\Auth;

class DisciplineIncidentController extends Controller
{
    public function index(Student $student)
    {
        $user = Auth::user();

        if ($user->can(P::DISCIPLINE_MANAGE)) {
            $this->authorizeStaffAccess($user, $student);
        } elseif ($user->hasRole('parent')) {
            abort_unless($user->wards()->where('students.id', $student->id)->exists(), 403);
        } else {
            abort(403);
        }

        $incidents = $student->disciplineIncidents()->with('reportedBy')->get();

        return view('discipline.index', compact('student', 'incidents'));
    }

    public function create(Student $student)
    {
        $user = Auth::user();
        abort_unless($user->can(P::DISCIPLINE_MANAGE), 403);
        $this->authorizeStaffAccess($user, $student);

        return view('discipline.create', compact('student'));
    }

    public function store(StoreDisciplineIncidentRequest $request, Student $student)
    {
        $user = $request->user();
        $this->authorizeStaffAccess($user, $student);

        $incident = $student->disciplineIncidents()->create(array_merge($request->validated(), [
            'reported_by' => $user->id,
        ]));

        $student->loadMissing('guardians');
        $guardian = $student->guardians->firstWhere('pivot.is_primary', true) ?? $student->guardians->first();
        $guardian?->notify(new DisciplineIncidentLogged($incident));

        return redirect()->route('discipline-incidents.index', $student)->with('success', __('discipline_records.created'));
    }

    public function edit(DisciplineIncident $incident)
    {
        $user = Auth::user();
        abort_unless($user->can(P::DISCIPLINE_MANAGE), 403);
        $this->authorizeStaffAccess($user, $incident->student);

        return view('discipline.edit', compact('incident'));
    }

    public function update(StoreDisciplineIncidentRequest $request, DisciplineIncident $incident)
    {
        $this->authorizeStaffAccess($request->user(), $incident->student);

        $incident->update($request->validated());

        return redirect()->route('discipline-incidents.index', $incident->student_id)
            ->with('success', __('discipline_records.updated'));
    }

    /**
     * discipline.manage is held by both teacher and principal, unlike
     * homework (teacher-only) or staff evaluations (principal-only) — both
     * roles actively log incidents. A teacher is additionally scoped to
     * their own sections here; principal/admin/owner are not.
     */
    private function authorizeStaffAccess(User $user, Student $student): void
    {
        if (! $user->hasRole('teacher')) {
            return;
        }

        abort_unless($user->staff && $user->staff->canAccessStudent($student), 403);
    }
}
