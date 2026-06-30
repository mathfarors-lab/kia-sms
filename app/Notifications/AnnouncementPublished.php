<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementPublished extends Notification
{
    use Queueable;

    public function __construct(public readonly Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'announcement',
            'announcement_id'  => $this->announcement->id,
            'title'            => $this->announcement->title,
            'body_en'          => $this->announcement->body_en,
            'url'              => route('announcements.show', $this->announcement),
        ];
    }
}
