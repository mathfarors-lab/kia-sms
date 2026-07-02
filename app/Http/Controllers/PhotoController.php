<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    private const PLACEHOLDER = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
  <rect width="200" height="200" fill="#e2e8f0"/>
  <circle cx="100" cy="80" r="35" fill="#94a3b8"/>
  <ellipse cx="100" cy="155" rx="55" ry="35" fill="#94a3b8"/>
</svg>
SVG;

    public function student(Request $request, Student $student)
    {
        $user = $request->user();

        $allowed = match (true) {
            $user->hasAnyRole(['admin', 'principal', 'teacher', 'accountant', 'librarian', 'receptionist']) => true,
            $user->hasRole('parent')   => $user->wards()->where('students.id', $student->id)->exists(),
            $user->hasRole('student')  => $student->user_id === $user->id,
            default                    => false,
        };

        if (!$allowed) {
            abort(403);
        }

        return $this->stream($student->photo);
    }

    public function staff(Request $request, Staff $staff)
    {
        $user = $request->user();

        $allowed = $user->hasAnyRole(['admin', 'principal'])
                || $user->id === $staff->user_id;

        if (!$allowed) {
            abort(403);
        }

        return $this->stream($staff->photo);
    }

    private function stream(?string $path)
    {
        $disk = Storage::disk('local');

        if ($path && $disk->exists($path)) {
            $absPath = $disk->path($path);
            $mime    = $disk->mimeType($path) ?: 'image/jpeg';

            return response()->file($absPath, [
                'Content-Type'           => $mime,
                'Cache-Control'          => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response(self::PLACEHOLDER, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
