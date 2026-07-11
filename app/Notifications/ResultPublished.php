<?php

namespace App\Notifications;

use App\Models\Exam;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResultPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Exam $exam) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        return __('sms.result_published', ['exam' => $this->exam->name]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name     = $notifiable->name;
        $examName = $this->exam->name;

        return (new MailMessage)
            ->subject(__('notification.result_published_subject', ['exam' => $examName]))
            ->greeting(__('notification.greeting', ['name' => $name]))
            ->line(__('notification.result_published_body', ['exam' => $examName]))
            ->action(__('notification.view_result'), route('term-results.index'))
            ->salutation(__('notification.salutation'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'      => "Results Published: {$this->exam->name}",
            'body'       => "Results for {$this->exam->name} have been published.",
            'url'        => route('term-results.index'),
            'icon'       => 'result',
            'exam_id'    => $this->exam->id,
            'exam_name'  => $this->exam->name,
            'message_en' => "Results for {$this->exam->name} have been published.",
            'message_km' => "លទ្ធផលនៃ {$this->exam->name} ត្រូវបានផ្សព្វផ្សាយ។",
        ];
    }
}
