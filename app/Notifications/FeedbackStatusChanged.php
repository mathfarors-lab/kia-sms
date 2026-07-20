<?php

namespace App\Notifications;

use App\Models\FeedbackItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FeedbackStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public readonly FeedbackItem $feedbackItem,
        public readonly string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'feedback_status',
            'feedback_item_id' => $this->feedbackItem->id,
            'title'            => $this->feedbackItem->subject,
            'body'             => __('feedback.notification_status_body', ['status' => __('feedback.status_' . $this->newStatus)]),
            'url'              => route('feedback.show', $this->feedbackItem),
        ];
    }
}
