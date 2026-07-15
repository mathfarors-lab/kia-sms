<?php

namespace App\Console\Commands;

use App\Models\IssuedDocument;
use App\Models\Scopes\BranchScope;
use App\Models\Staff;
use App\Models\Student;
use App\Services\DocumentIssuanceService;
use App\Support\BranchContext;
use Illuminate\Console\Command;

/**
 * Retroactively issues ID cards/certificates for students and staff that
 * predate the auto-issue feature — anyone enrolled, hired, graduated, or
 * withdrawn before that shipped never got a row in issued_documents, so
 * their Documents section stays empty until someone happens to open their
 * ID card or certificate page (which lazily backfills one at a time).
 *
 * Every call goes through DocumentIssuanceService, which is itself
 * idempotent (firstOrCreate backed by a unique index) — running this
 * command again after new records are added, or twice by mistake, never
 * issues a duplicate or renumbers an existing document.
 */
class BackfillDocuments extends Command
{
    protected $signature = 'kia:backfill-documents';
    protected $description = 'Issue ID cards/certificates for existing students and staff that predate auto-issue — safe to run more than once';

    public function handle(DocumentIssuanceService $documents): int
    {
        $newCount     = 0;
        $studentTotal = 0;
        $staffTotal   = 0;

        Student::withoutGlobalScope(BranchScope::class)->chunkById(200, function ($students) use ($documents, &$newCount, &$studentTotal) {
            foreach ($students as $student) {
                $studentTotal++;

                $types = match ($student->status) {
                    'enrolled'                => [IssuedDocument::TYPE_ID_CARD, IssuedDocument::TYPE_ENROLLMENT_CERT],
                    'graduated'                => [IssuedDocument::TYPE_GRADUATION_CERT],
                    'transferred', 'dropped'   => [IssuedDocument::TYPE_LEAVING_CERT],
                    default                    => [],
                };

                foreach ($types as $type) {
                    $doc = BranchContext::within($student->branch_id, fn () => $documents->issueForStudent($student, $type));
                    if ($doc->wasRecentlyCreated) {
                        $newCount++;
                    }
                }
            }
        });

        Staff::withoutGlobalScope(BranchScope::class)->chunkById(200, function ($staffMembers) use ($documents, &$newCount, &$staffTotal) {
            foreach ($staffMembers as $staff) {
                $staffTotal++;

                $doc = BranchContext::within($staff->branch_id, fn () => $documents->issueForStaff($staff));
                if ($doc->wasRecentlyCreated) {
                    $newCount++;
                }
            }
        });

        $this->info("Checked {$studentTotal} student(s) and {$staffTotal} staff member(s) — issued {$newCount} new document(s).");
        $this->line('Safe to run again: existing documents are never touched, renumbered, or duplicated.');

        return self::SUCCESS;
    }
}
