<?php

namespace App\Notifications;

use App\Models\FeedbackItem;
use App\Models\FeedbackReply;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class FeedbackReplied extends Notification
{
    use Queueable;

    public function __construct(
        public readonly FeedbackItem $feedbackItem,
        public readonly FeedbackReply $reply,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'              => 'feedback_reply',
            'feedback_item_id'  => $this->feedbackItem->id,
            'title'             => $this->feedbackItem->subject,
            'body'              => Str::limit($this->reply->body, 100),
            'url'               => route('feedback.show', $this->feedbackItem),
        ];
    }
}
