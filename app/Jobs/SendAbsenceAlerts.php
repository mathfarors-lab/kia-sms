<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAbsenceAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $sectionId,
        public readonly array $studentIds,
    ) {}

    public function handle(): void
    {
        foreach ($this->studentIds as $studentId) {
            Log::info("Absence alert: student {$studentId} absent from section {$this->sectionId} today.");
        }
    }
}
