<?php

namespace App\Listeners;

use App\Events\DueAssigned;
use App\Notifications\PaymentDueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPaymentDueNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DueAssigned $event): void
    {
        // Only notify if due date is coming up (within 7 days)
        if ($event->term->due_date && $event->term->due_date->diffInDays(now()) <= 7 && $event->term->due_date->isFuture()) {
            $event->user->notify(new PaymentDueNotification(
                $event->term->term_name,
                (float) $event->term->balance,
                $event->term->due_date,
            ));
        }
    }
}
