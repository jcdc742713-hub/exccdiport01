<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class PaymentConfirmed extends Notification
{
    use Queueable;

    public function __construct(
        private int $transactionId,
        private float $amount,
        private string $reference,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Confirmation - ₱' . number_format($this->amount, 2))
            ->greeting('Payment Received!')
            ->line("Your payment of **₱" . number_format($this->amount, 2) . "** has been successfully recorded.")
            ->line("**Transaction ID:** {$this->reference}")
            ->line("**Reference Number:** PAY-{$this->transactionId}")
            ->line('Check your account for payment application details.')
            ->action('View Account', route('student.account', ['tab' => 'history']))
            ->line('Thank you for your payment!');
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'payment_confirmed',
            'title' => 'Payment Recorded',
            'message' => "Payment of ₱" . number_format($this->amount, 2) . " has been recorded.",
            'reference' => $this->reference,
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'icon' => 'check-circle',
            'color' => 'green',
        ]);
    }
}
