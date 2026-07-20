<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\StoreStaffDocumentRequest;
use App\Models\Staff;
use App\Models\StaffDocument;
use App\Support\Permissions as P;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffDocumentController extends Controller
{
    private const DISK = 'local'; // private disk — served via gated download() below

    public function store(StoreStaffDocumentRequest $request, Staff $staff)
    {
        $this->authorize('staff.edit');

        $file = $request->file('file');

        StaffDocument::create([
            'staff_id' => $staff->id,
            'label' => $request->string('label'),
            'path' => $file->store('staff/documents', self::DISK),
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', __('Document uploaded.'));
    }

    public function destroy(StaffDocument $document)
    {
        $this->authorize('staff.edit');

        Storage::disk(self::DISK)->delete($document->path);
        $document->delete();

        return back()->with('success', __('Document deleted.'));
    }

    /** Gated download — staff holding staff.view, or the staff member themselves (their own CV). */
    public function download(Request $request, StaffDocument $document)
    {
        $this->authorizeView($request, $document);

        abort_unless(Storage::disk(self::DISK)->exists($document->path), 404);

        return response()->download(
            Storage::disk(self::DISK)->path($document->path),
            $document->original_name
        );
    }

    private function authorizeView(Request $request, StaffDocument $document): void
    {
        $user = $request->user();

        if ($user->can(P::STAFF_VIEW)) {
            return;
        }

        if ($user->id === $document->staff->user_id) {
            return;
        }

        abort(403);
    }
}
