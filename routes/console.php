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
