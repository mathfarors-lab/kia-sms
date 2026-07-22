<?php

namespace App\Http\Controllers;

use App\Imports\StudentsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StudentImportController extends Controller
{
    public function showForm()
    {
        $this->authorize('students.create');
        return view('students.import');
    }

    public function import(Request $request)
    {
        $this->authorize('students.create');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $import = new StudentsImport();
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $errors = $import->getErrors();

            if ($successCount > 0) {
                session()->flash('message', "Successfully imported $successCount student(s)");
            }

            if (!empty($errors)) {
                session()->flash('errors', $errors);
            }

            return redirect()->route('students.index')
                ->with('success', $successCount > 0 ? "Imported $successCount students" : 'No students imported')
                ->with('errors', $errors ?: null);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
