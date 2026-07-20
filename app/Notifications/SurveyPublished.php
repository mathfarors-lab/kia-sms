<?php

namespace App\Notifications;

use App\Models\Survey;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SurveyPublished extends Notification
{
    use Queueable;

    public function __construct(public readonly Survey $survey) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'survey',
            'survey_id' => $this->survey->id,
            'title' => $this->survey->title_en,
            'url' => route('surveys.take', $this->survey),
        ];
    }
}
