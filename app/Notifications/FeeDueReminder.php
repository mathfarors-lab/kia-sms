<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeeDueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        return __('sms.fee_due', [
            'number' => $this->invoice->number,
            'amount' => number_format((float) $this->invoice->remainingBalance(), 2),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isOverdue = $this->invoice->status === 'overdue';
        $subject   = $isOverdue
            ? __('notification.fee_overdue_subject', ['number' => $this->invoice->number])
            : __('notification.fee_due_subject', ['number' => $this->invoice->number]);

        return (new MailMessage)
            ->subject($subject)
            ->greeting(__('notification.greeting', ['name' => $notifiable->name]))
            ->line($isOverdue
                ? __('notification.fee_overdue_body', ['number' => $this->invoice->number, 'amount' => number_format((float) $this->invoice->remainingBalance(), 2)])
                : __('notification.fee_due_body', ['number' => $this->invoice->number, 'due' => $this->invoice->due_date?->format('d M Y'), 'amount' => number_format((float) $this->invoice->total, 2)])
            )
            ->action(__('notification.view_invoice'), route('invoices.show', $this->invoice))
            ->salutation(__('notification.salutation'));
    }

    public function toArray(object $notifiable): array
    {
        $isOverdue = $this->invoice->status === 'overdue';
        return [
            'title'          => $isOverdue
                ? "Overdue Invoice: {$this->invoice->number}"
                : "Fee Due: {$this->invoice->number}",
            'body'           => $isOverdue
                ? "Invoice {$this->invoice->number} has an overdue balance."
                : "Invoice {$this->invoice->number} is due soon.",
            'url'            => route('invoices.show', $this->invoice),
            'icon'           => 'invoice',
            'invoice_id'     => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'amount'         => $this->invoice->total,
            'status'         => $this->invoice->status,
            'message_en'     => "Invoice {$this->invoice->number} is {$this->invoice->status}.",
            'message_km'     => "វិក្កយបត្រ {$this->invoice->number} ស្ថិតក្នុងស្ថានភាព {$this->invoice->status}។",
        ];
    }
}
