<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Notifications\AnnouncementPublished;
use App\Services\AnnouncementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAnnouncementNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $announcementId) {}

    public function handle(AnnouncementService $service): void
    {
        $announcement = Announcement::find($this->announcementId);
        if (! $announcement || ! $announcement->isPublished()) {
            return;
        }

        $notification = new AnnouncementPublished($announcement);

        // chunkById ensures we never load all users into memory at once
        $service->recipientQuery($announcement)
            ->chunkById(100, function ($users) use ($notification) {
                foreach ($users as $user) {
                    $user->notify(clone $notification);
                }
            });
    }
}
