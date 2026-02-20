<?php

namespace App\Providers;

use App\Events\PaymentRecorded;
use App\Events\DueAssigned;
use App\Events\PaymentReminderGenerated;
use App\Listeners\GeneratePaymentReceivedReminder;
use App\Listeners\GenerateDueAssignedReminder;
use App\Listeners\MarkNotificationCompleteOnPayment;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentRecorded::class => [
            GeneratePaymentReceivedReminder::class,
            MarkNotificationCompleteOnPayment::class,
        ],
        DueAssigned::class => [
            GenerateDueAssignedReminder::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}