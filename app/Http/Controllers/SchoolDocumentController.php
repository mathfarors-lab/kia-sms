<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolDocument\StoreSchoolDocumentRequest;
use App\Models\SchoolDocument;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolDocumentController extends Controller
{
    private const DISK = 'local'; // private disk — served via gated download() below

    public function index(Request $request)
    {
        $this->authorize('documents.view');

        $category = $request->query('category');

        $documents = SchoolDocument::query()
            ->visibleToBranch()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->with('uploader')
            ->latest()
            ->get();

        return view('school-documents.index', compact('documents', 'category'));
    }

    public function create()
    {
        $this->authorize('documents.manage');

        return view('school-documents.create');
    }

    public function store(StoreSchoolDocumentRequest $request)
    {
        $this->authorize('documents.manage');

        $file = $request->file('file');

        SchoolDocument::create([
            'title' => $request->string('title'),
            'category' => $request->string('category'),
            'path' => $file->store('school-documents', self::DISK),
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => $request->user()->id,
            'branch_id' => $request->boolean('all_branches') ? null : BranchContext::current(),
        ]);

        return redirect()->route('school-documents.index')->with('success', __('school_documents.created'));
    }

    public function destroy(SchoolDocument $document)
    {
        $this->authorize('documents.manage');

        Storage::disk(self::DISK)->delete($document->path);
        $document->delete();

        return redirect()->route('school-documents.index')->with('success', __('school_documents.deleted'));
    }

    public function download(Request $request, SchoolDocument $document)
    {
        $this->authorize('documents.view');

        // Re-check branch visibility server-side, not just permission —
        // documents.view is a blanket permission, but a branch-specific
        // document shouldn't be fetchable by guessing another branch's
        // document id. Reuses the exact same scope as index() so the two
        // can never drift apart.
        abort_unless(SchoolDocument::query()->visibleToBranch()->whereKey($document->id)->exists(), 403);

        abort_unless(Storage::disk(self::DISK)->exists($document->path), 404);

        return response()->download(Storage::disk(self::DISK)->path($document->path), $document->original_name);
    }
}
