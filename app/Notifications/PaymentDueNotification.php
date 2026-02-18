<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class PaymentDueNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $termName,
        private float $balance,
        private Carbon $dueDate,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysUntilDue = $this->dueDate->diffInDays(now());

        return (new MailMessage)
            ->subject("Payment Due Soon - {$this->termName}")
            ->greeting('Reminder: Payment Due')
            ->line("A payment for **{$this->termName}** is due soon.")
            ->line("**Amount:** ₱" . number_format($this->balance, 2))
            ->line("**Due Date:** " . $this->dueDate->format('F d, Y') . " ({$daysUntilDue} days remaining)")
            ->action('Make Payment', route('student.account', ['tab' => 'payment']))
            ->line('Please ensure timely payment to avoid penalties.');
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'payment_due',
            'title' => "{$this->termName} Payment Due",
            'message' => "Amount due: ₱" . number_format($this->balance, 2) . " by " . $this->dueDate->format('M d, Y'),
            'term_name' => $this->termName,
            'amount' => $this->balance,
            'due_date' => $this->dueDate->toDateString(),
            'icon' => 'alert-circle',
            'color' => 'warning',
        ]);
    }
}
