<?php

namespace App\Notifications;

use App\Models\DisciplineIncident;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisciplineIncidentLogged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DisciplineIncident $incident) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        $student = $this->incident->student;

        return __('sms.discipline_incident', [
            'name' => $student->name_km ?: $student->name_en,
            'type' => __('discipline_records.type_'.$this->incident->type),
            'date' => $this->incident->incident_date->format('d M Y'),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $student = $this->incident->student;
        $type = __('discipline_records.type_'.$this->incident->type);

        return (new MailMessage)
            ->subject(__('notification.discipline_incident_subject', ['name' => $student->name_en]))
            ->greeting(__('notification.greeting', ['name' => $notifiable->name]))
            ->line(__('notification.discipline_incident_body', [
                'name' => $student->name_en, 'type' => $type, 'date' => $this->incident->incident_date->format('d M Y'),
            ]))
            ->action(__('notification.view_incident'), route('parent.child.show', $student))
            ->salutation(__('notification.salutation'));
    }

    public function toArray(object $notifiable): array
    {
        $student = $this->incident->student;
        $name = $student->name_km ?: $student->name_en;
        $type = __('discipline_records.type_'.$this->incident->type);

        return [
            'title' => 'Discipline Incident',
            'body' => "{$student->name_en} — {$type}",
            'icon' => 'discipline',
            'student_id' => $student->id,
            'incident_id' => $this->incident->id,
            'message_en' => "A discipline incident ({$type}) was logged for {$student->name_en}.",
            'message_km' => "ព្រឹត្តិការណ៍វិន័យ ({$type}) ត្រូវបានកត់ត្រាសម្រាប់ {$name}។",
            'url' => route('parent.child.show', $student),
        ];
    }
}
