<?php

namespace App\Http\Controllers;

use App\Models\ReportComment;
use Illuminate\Http\Request;

class ReportCommentController extends Controller
{
    public function index()
    {
        $this->authorize('report-comments.manage');

        $comments = ReportComment::orderBy('category')->orderBy('id')->get()->groupBy('category');

        return view('report-comments.index', compact('comments'));
    }

    public function store(Request $request)
    {
        $this->authorize('report-comments.manage');

        $data = $request->validate([
            'category' => ['nullable', 'string', 'max:50'],
            'text_en'  => ['required', 'string', 'max:1000'],
            'text_km'  => ['nullable', 'string', 'max:1000'],
        ]);

        ReportComment::create($data);

        return back()->with('success', __('report_comments.created'));
    }

    public function update(Request $request, ReportComment $comment)
    {
        $this->authorize('report-comments.manage');

        $data = $request->validate([
            'category' => ['nullable', 'string', 'max:50'],
            'text_en'  => ['required', 'string', 'max:1000'],
            'text_km'  => ['nullable', 'string', 'max:1000'],
        ]);

        $comment->update($data);

        return back()->with('success', __('report_comments.updated'));
    }

    public function destroy(ReportComment $comment)
    {
        $this->authorize('report-comments.manage');

        $comment->delete();

        return back()->with('success', __('report_comments.deleted'));
    }
}
