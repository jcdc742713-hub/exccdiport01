<?php

namespace App\Providers;

use App\Events\WorkflowStepAdvanced;
use App\Events\PaymentRecorded;
use App\Events\DueAssigned;
use App\Listeners\SendWorkflowNotification;
use App\Listeners\SendPaymentConfirmationNotification;
use App\Listeners\SendPaymentDueNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        WorkflowStepAdvanced::class => [
            SendWorkflowNotification::class,
        ],
        PaymentRecorded::class => [
            SendPaymentConfirmationNotification::class,
        ],
        DueAssigned::class => [
            SendPaymentDueNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}