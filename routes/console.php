<?php

use Illuminate\Support\Facades\Schedule;

/**
 * Bakong KHQR polling — runs every minute.
 *
 * ⚠️ CAMBODIA-SERVER REQUIREMENT: The server running this schedule MUST have a
 *    Cambodia-based IP. NBC blocks check_transaction_by_md5 calls from outside
 *    Cambodia. In production this means your web/queue server must be hosted
 *    inside Cambodia (e.g. a Cambodian VPS or cloud region with KH egress).
 *
 * The 1-minute cadence gives ~1-min payment confirmation latency, appropriate
 * for a school invoice flow. Reduce to everyThirtySeconds() only if NBC grants
 * a higher API rate limit for your merchant account.
 */
Schedule::command('bakong:check-transactions')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

/**
 * Nightly backups (spatie/laravel-backup) — clean old archives first, then
 * take the new backup (DB + private storage → the "backups" disk).
 * Retention policy lives in config/backup.php. Restore procedure: see
 * BACKUP_RESTORE.md at the project root.
 */
Schedule::command('backup:clean')->dailyAt('01:30');
Schedule::command('backup:run')->dailyAt('02:00');

/**
 * Gate attendance: sweep students who never scanned in past each branch's
 * own gate_absent_cutoff setting. Idempotent (see the command's own
 * docblock) — every-15-minutes just controls how promptly a branch's
 * cutoff gets noticed, not correctness.
 */
Schedule::command('attendance:sweep-gate-absentees')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
