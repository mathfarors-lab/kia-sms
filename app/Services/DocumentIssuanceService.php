<?php

namespace App\Services;

use App\Models\IssuedDocument;
use App\Models\Staff;
use App\Models\Student;

/**
 * Issues (and re-finds, never re-numbers) documents for students and staff.
 * Every method here is idempotent: calling it again for the same
 * student/staff + type returns the already-issued row untouched, backed by
 * a unique DB index on (student_id, type) / (staff_id, type) — not just an
 * application-level check.
 */
class DocumentIssuanceService
{
    /** Maps a persisted document type to DocumentService::nextCertNumber()'s short code. */
    private const CERT_SEQUENCE_KEYS = [
        IssuedDocument::TYPE_ENROLLMENT_CERT => 'enroll',
        IssuedDocument::TYPE_GRADUATION_CERT => 'grad',
        IssuedDocument::TYPE_LEAVING_CERT    => 'leave',
    ];

    public function __construct(private DocumentService $docs) {}

    public function issueForStudent(Student $student, string $type): IssuedDocument
    {
        return IssuedDocument::firstOrCreate(
            ['student_id' => $student->id, 'type' => $type],
            [
                'number' => isset(self::CERT_SEQUENCE_KEYS[$type])
                    ? $this->docs->nextCertNumber(self::CERT_SEQUENCE_KEYS[$type])
                    : null,
                'issued_at' => now(),
            ]
        );
    }

    public function issueForStaff(Staff $staff): IssuedDocument
    {
        return IssuedDocument::firstOrCreate(
            ['staff_id' => $staff->id, 'type' => IssuedDocument::TYPE_ID_CARD],
            ['issued_at' => now()]
        );
    }
}
