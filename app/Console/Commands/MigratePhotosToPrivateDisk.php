<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * One-off idempotent migration: move student (and staff) photos
 * from storage/app/public → storage/app/private.
 *
 * Safe to run multiple times:
 *   - Already on private disk → skipped
 *   - On public but not private → moved
 *   - On neither → reported as missing
 */
class MigratePhotosToPrivateDisk extends Command
{
    protected $signature = 'photos:migrate-to-private
                            {--dry-run : Preview what would be moved without making changes}';

    protected $description = 'Move student/staff photos from the public disk to the private (local) disk';

    public function handle(): int
    {
        $dryRun  = $this->option('dry-run');
        $public  = Storage::disk('public');
        $private = Storage::disk('local');

        if ($dryRun) {
            $this->warn('[DRY RUN] No files will be moved.');
        }

        $moved   = 0;
        $skipped = 0;
        $missing = 0;

        // Students
        $this->info('— Students —');
        Student::withTrashed()->whereNotNull('photo')->each(
            function (Student $model) use ($public, $private, $dryRun, &$moved, &$skipped, &$missing) {
                [$m, $s, $x] = $this->migrateOne($model->photo, $public, $private, $dryRun);
                $moved   += $m; $skipped += $s; $missing += $x;
            }
        );

        // Staff
        $this->info('— Staff —');
        Staff::whereNotNull('photo')->each(
            function (Staff $model) use ($public, $private, $dryRun, &$moved, &$skipped, &$missing) {
                [$m, $s, $x] = $this->migrateOne($model->photo, $public, $private, $dryRun);
                $moved   += $m; $skipped += $s; $missing += $x;
            }
        );

        $this->newLine();
        $this->info("Done. Moved: {$moved}  Already migrated: {$skipped}  Missing: {$missing}");

        return self::SUCCESS;
    }

    /** Returns [moved, skipped, missing] counts for a single path. */
    private function migrateOne(
        string $path,
        $public,
        $private,
        bool $dryRun
    ): array {
        $onPrivate = $private->exists($path);
        $onPublic  = $public->exists($path);

        if ($onPrivate && !$onPublic) {
            $this->line("  SKIP  (already migrated): {$path}");
            return [0, 1, 0];
        }

        if (!$onPublic) {
            $this->warn("  MISS  (not on either disk): {$path}");
            return [0, 0, 1];
        }

        $this->line("  MOVE  {$path}");

        if (!$dryRun) {
            $private->put($path, $public->get($path));
            $public->delete($path);
        }

        return [1, 0, 0];
    }
}
