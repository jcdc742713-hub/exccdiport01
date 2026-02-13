<?php

namespace App\Providers;

use App\Events\WorkflowStepAdvanced;
use App\Listeners\SendWorkflowNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        WorkflowStepAdvanced::class => [
            SendWorkflowNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}