<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Events\WorkflowStepAdvanced;

class WorkflowService
{
    public function startWorkflow(Workflow $workflow, Model $entity, int $userId): WorkflowInstance
    {
        return DB::transaction(function () use ($workflow, $entity, $userId) {
            $firstStep = $workflow->steps[0] ?? null;
            
            if (!$firstStep) {
                throw new \Exception('Workflow has no steps defined');
            }

            $instance = WorkflowInstance::create([
                'workflow_id' => $workflow->id,
                'workflowable_type' => get_class($entity),
                'workflowable_id' => $entity->id,
                'current_step' => $firstStep['name'],
                'status' => 'in_progress',
                'step_history' => [],
                'initiated_by' => $userId,
            ]);

            $instance->addStepToHistory($firstStep['name'], [
                'action' => 'started',
                'user_id' => $userId,
            ]);

            // Create approval request if step requires approval
            if ($firstStep['requires_approval'] ?? false) {
                $this->createApprovalRequest($instance, $firstStep);
            }

            return $instance;
        });
    }

    public function advanceWorkflow(WorkflowInstance $instance, int $userId): void
    {
        DB::transaction(function () use ($instance, $userId) {
            $workflow = $instance->workflow;
            $currentStepIndex = $this->getStepIndex($workflow, $instance->current_step);
            $previousStep = $instance->current_step; // Store previous step
            
            if ($currentStepIndex === null) {
                throw new \Exception('Current step not found in workflow');
            }

            $nextStepIndex = $currentStepIndex + 1;
            
            if ($nextStepIndex >= count($workflow->steps)) {
                $instance->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                
                $instance->addStepToHistory('completed', [
                    'action' => 'completed',
                    'user_id' => $userId,
                ]);
                
                return;
            }

            $nextStep = $workflow->steps[$nextStepIndex];
            
            $instance->update([
                'current_step' => $nextStep['name'],
            ]);

            $instance->addStepToHistory($nextStep['name'], [
                'action' => 'advanced',
                'user_id' => $userId,
            ]);

            if ($nextStep['requires_approval'] ?? false) {
                $this->createApprovalRequest($instance, $nextStep);
            }

            // Dispatch event after successful advancement
            WorkflowStepAdvanced::dispatch($instance, $previousStep, $nextStep['name']);
        });
    }

    public function approveStep(WorkflowApproval $approval, int $userId, ?string $comments = null): void
    {
        DB::transaction(function () use ($approval, $userId, $comments) {
            $approval->approve($comments);
            
            // Check if all approvals for this step are approved
            $pendingApprovals = WorkflowApproval::where('workflow_instance_id', $approval->workflow_instance_id)
                ->where('step_name', $approval->step_name)
                ->where('status', 'pending')
                ->count();

            if ($pendingApprovals === 0) {
                // All approvals done, advance workflow
                $this->advanceWorkflow($approval->workflowInstance, $userId);
            }
        });
    }

    public function rejectStep(WorkflowApproval $approval, int $userId, string $comments): void
    {
        DB::transaction(function () use ($approval, $userId, $comments) {
            $approval->reject($comments);
            
            $approval->workflowInstance->update([
                'status' => 'rejected',
            ]);

            $approval->workflowInstance->addStepToHistory($approval->step_name, [
                'action' => 'rejected',
                'user_id' => $userId,
                'comments' => $comments,
            ]);
        });
    }

    protected function createApprovalRequest(WorkflowInstance $instance, array $step): void
    {
        $approvers = $step['approvers'] ?? [];
        
        foreach ($approvers as $approverId) {
            WorkflowApproval::create([
                'workflow_instance_id' => $instance->id,
                'step_name' => $step['name'],
                'approver_id' => $approverId,
                'status' => 'pending',
            ]);
        }
    }

    protected function getStepIndex(Workflow $workflow, string $stepName): ?int
    {
        foreach ($workflow->steps as $index => $step) {
            if ($step['name'] === $stepName) {
                return $index;
            }
        }
        return null;
    }
}