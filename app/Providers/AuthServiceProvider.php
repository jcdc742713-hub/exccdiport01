<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Notification;
use App\Policies\UserPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\StudentFeePolicy;
use App\Models\WorkflowApproval;
use App\Policies\WorkflowApprovalPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Notification::class => NotificationPolicy::class,
        WorkflowApproval::class => WorkflowApprovalPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}