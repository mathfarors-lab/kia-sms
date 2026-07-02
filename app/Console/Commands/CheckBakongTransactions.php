<?php

namespace App\Console\Commands;

use App\Models\PaymentIntent;
use App\Services\BakongPollingService;
use App\Services\KhqrService;
use Illuminate\Console\Command;

class CheckBakongTransactions extends Command
{
    protected $signature   = 'bakong:check-transactions';
    protected $description = 'Poll Bakong check_transaction_by_md5 for all pending KHQR payment intents';

    public function handle(KhqrService $khqr, BakongPollingService $poller): int
    {
        // 1. Expire any intents whose QR window has closed — stop polling them.
        $expired = $khqr->expireStaleIntents();
        if ($expired > 0) {
            $this->line("Marked {$expired} intent(s) as expired.");
        }

        // 2. Collect remaining pending intents and poll in chunks of 50
        //    (Bakong API bulk limit per call).
        $count = PaymentIntent::pending()->count();

        if ($count === 0) {
            $this->line('No pending intents — nothing to poll.');
            return self::SUCCESS;
        }

        $this->line("Polling {$count} pending intent(s)...");

        PaymentIntent::pending()
            ->with('invoice')
            ->chunkById(50, function ($chunk) use ($poller) {
                $poller->checkChunk($chunk);
            });

        $this->info('Done.');
        return self::SUCCESS;
    }
}
