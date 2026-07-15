<?php

namespace App\Notifications;

use App\Models\Student;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Gate arrival/departure alert to the primary guardian. SMS + in-app only
 * (not mail) — this is a time-sensitive, transactional ping, not the kind
 * of thing worth an email for.
 */
class StudentGateEvent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Student $student,
        public string $eventType, // 'arrival' | 'departure'
        public Carbon $time,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        $key = $this->eventType === 'arrival' ? 'sms.gate_arrival' : 'sms.gate_departure';

        return __($key, [
            'name' => $this->student->name_km ?: $this->student->name_en,
            'time' => $this->time->format('g:i A'),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        $name = $this->student->name_km ?: $this->student->name_en;

        return [
            'title'      => $this->eventType === 'arrival' ? 'Gate Arrival' : 'Gate Departure',
            'body'       => "{$name} — {$this->eventType} at {$this->time->format('g:i A')}",
            'icon'       => 'gate',
            'student_id' => $this->student->id,
            'event_type' => $this->eventType,
            'time'       => $this->time->format('g:i A'),
            'message_en' => "{$this->student->name_en} {$this->eventType} at school at {$this->time->format('g:i A')}.",
            'message_km' => "{$name} បាន" . ($this->eventType === 'arrival' ? 'មកដល់សាលា' : 'ចាកចេញពីសាលា') . "នៅម៉ោង {$this->time->format('g:i A')}។",
            'url'        => route('parent.child.show', $this->student),
        ];
    }
}
