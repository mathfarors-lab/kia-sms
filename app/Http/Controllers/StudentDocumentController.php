<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreStudentDocumentRequest;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Support\Permissions as P;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentDocumentController extends Controller
{
    private const DISK = 'local'; // private disk — served via gated download() below

    public function store(StoreStudentDocumentRequest $request, Student $student)
    {
        $this->authorize('students.edit');

        $file = $request->file('file');

        StudentDocument::create([
            'student_id'    => $student->id,
            'label'         => $request->string('label'),
            'path'          => $file->store('students/documents', self::DISK),
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by'   => $request->user()->id,
        ]);

        return back()->with('success', __('Document uploaded.'));
    }

    public function destroy(StudentDocument $document)
    {
        $this->authorize('students.edit');

        Storage::disk(self::DISK)->delete($document->path);
        $document->delete();

        return back()->with('success', __('Document deleted.'));
    }

    /** Gated download — staff holding students.view, or the student's own parent. */
    public function download(Request $request, StudentDocument $document)
    {
        $this->authorizeView($request, $document);

        abort_unless(Storage::disk(self::DISK)->exists($document->path), 404);

        return response()->download(
            Storage::disk(self::DISK)->path($document->path),
            $document->original_name
        );
    }

    private function authorizeView(Request $request, StudentDocument $document): void
    {
        $user = $request->user();

        // students.view is shared broadly (accountant, receptionist, librarian
        // all legitimately need every student). Teacher is the one holder
        // that must be scoped to their own accessible students.
        if ($user->hasRole('teacher')) {
            abort_unless($user->staff && $user->staff->canAccessStudent($document->student), 403);
            return;
        }

        if ($user->can(P::STUDENTS_VIEW)) {
            return;
        }

        if ($user->hasRole('parent') && $user->wards()->where('students.id', $document->student_id)->exists()) {
            return;
        }

        abort(403);
    }
}
