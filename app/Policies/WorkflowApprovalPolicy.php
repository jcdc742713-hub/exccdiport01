<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowApproval;

class WorkflowApprovalPolicy
{
    public function view(User $user, WorkflowApproval $approval): bool
    {
        return $approval->approver_id === $user->id;
    }

    public function approve(User $user, WorkflowApproval $approval): bool
    {
        return $approval->approver_id === $user->id 
            && $approval->status === 'pending';
    }
}