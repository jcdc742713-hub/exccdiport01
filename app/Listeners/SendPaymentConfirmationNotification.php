<?php

namespace App\Listeners;

use App\Events\PaymentRecorded;
use App\Notifications\PaymentConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPaymentConfirmationNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PaymentRecorded $event): void
    {
        $event->user->notify(new PaymentConfirmed(
            $event->transactionId,
            $event->amount,
            $event->reference,
        ));
    }
}
