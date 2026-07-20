<?php

namespace App\Jobs;

use App\Models\Survey;
use App\Notifications\SurveyPublished;
use App\Services\SurveyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSurveyNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $surveyId) {}

    public function handle(SurveyService $service): void
    {
        $survey = Survey::find($this->surveyId);
        if (! $survey || $survey->status !== 'open') {
            return;
        }

        $notification = new SurveyPublished($survey);

        // chunkById ensures we never load all recipients into memory at once.
        $service->recipientQuery($survey)
            ->chunkById(100, function ($users) use ($notification) {
                foreach ($users as $user) {
                    $user->notify(clone $notification);
                }
            });
    }
}
