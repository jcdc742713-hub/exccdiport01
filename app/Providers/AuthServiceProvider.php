<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\StudentFeePolicy;
use App\Models\WorkflowApproval;
use App\Policies\WorkflowApprovalPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => StudentFeePolicy::class,
        WorkflowApproval::class => WorkflowApprovalPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}