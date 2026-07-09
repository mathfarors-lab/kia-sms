<?php

namespace App\Http\Controllers;

use App\Http\Requests\IssueBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Student;
use App\Services\LibraryService;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function __construct(private LibraryService $library) {}

    public function overdueIssues()
    {
        Gate::authorize(Permissions::BOOK_ISSUES_VIEW);

        $issues = BookIssue::with(['book', 'student'])
            ->whereNull('returned_at')
            ->where('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->paginate(25);

        return view('books.overdue', compact('issues'));
    }

    public function index()
    {
        $this->authorize('viewAny', Book::class);
        $books = Book::orderBy('title')->paginate(25);
        return view('books.index', compact('books'));
    }

    public function create()
    {
        $this->authorize('create', Book::class);
        return view('books.create');
    }

    public function store(StoreBookRequest $request)
    {
        $this->authorize('create', Book::class);

        $data = $request->validated();
        $data['available_copies'] = $data['total_copies'];

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('book-covers', 'local');
        }
        unset($data['cover']);

        Book::create($data);

        return redirect()->route('books.index')
            ->with('success', __('engagement.book_saved'));
    }

    public function show(Book $book)
    {
        $this->authorize('view', $book);
        $issues = $book->issues()->with('student')->latest()->paginate(10);
        return view('books.show', compact('book', 'issues'));
    }

    public function edit(Book $book)
    {
        $this->authorize('update', $book);
        return view('books.edit', compact('book'));
    }

    public function update(Request $request, Book $book)
    {
        $this->authorize('update', $book);

        $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'author'   => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'cover'    => ['nullable', 'file', 'max:5120', 'mimes:png,jpg,jpeg'],
        ]);

        $data = $request->only('title', 'author', 'category');

        if ($request->hasFile('cover')) {
            if ($book->cover_path) Storage::disk('local')->delete($book->cover_path);
            $data['cover_path'] = $request->file('cover')->store('book-covers', 'local');
        }

        $book->update($data);

        return redirect()->route('books.show', $book)
            ->with('success', __('engagement.book_saved'));
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);
        $book->delete();
        return redirect()->route('books.index');
    }

    // GET /books/{book}/issue
    public function issueForm(Book $book)
    {
        $this->authorize('issue', $book);
        $students = Student::orderBy('name_en')->get();
        return view('books.issue', compact('book', 'students'));
    }

    // POST /books/{book}/issue
    public function issue(IssueBookRequest $request, Book $book)
    {
        $this->authorize('issue', $book);

        $student = Student::findOrFail($request->student_id);
        $this->library->issue($book, $student, $request->user(), $request->due_date);

        return redirect()->route('books.show', $book)
            ->with('success', __('engagement.book_issued'));
    }

    // POST /book-issues/{issue}/return
    public function returnBook(BookIssue $issue)
    {
        $this->authorize('return_', $issue->book);
        $issue = $this->library->returnBook($issue);

        $msg = $issue->fine_amount > 0
            ? __('engagement.book_returned_fine', ['fine' => $issue->fine_amount])
            : __('engagement.book_returned');

        return redirect()->route('books.show', $issue->book)->with('success', $msg);
    }
}
