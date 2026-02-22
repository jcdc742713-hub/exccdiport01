<?php

namespace Database\Seeders;

use App\Models\Workflow;
use App\Models\User;
use App\Enums\UserRoleEnum;
use Illuminate\Database\Seeder;

class PaymentApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Avoid duplicates on re-run
        if (Workflow::where('type', 'payment_approval')->exists()) {
            $this->command->info('Payment approval workflow already exists, skipping.');
            return;
        }

        // Get all accounting user IDs to be approvers
        $accountingUserIds = User::where('role', UserRoleEnum::ACCOUNTING->value)
            ->pluck('id')
            ->toArray();

        // Fallback to admin if no accounting users exist yet
        if (empty($accountingUserIds)) {
            $accountingUserIds = User::where('role', 'admin')
                ->pluck('id')
                ->toArray();
        }

        Workflow::create([
            'name'        => 'Student Payment Approval',
            'type'        => 'payment_approval',
            'description' => 'Student-submitted payments require accounting verification before being marked as paid.',
            'is_active'   => true,
            'steps'       => [
                [
                    'name'             => 'Payment Submitted',
                    'description'      => 'Student has submitted a payment. Awaiting accounting review.',
                    'requires_approval' => false,
                ],
                [
                    'name'             => 'Accounting Verification',
                    'description'      => 'Accounting staff verifies the payment details and amount.',
                    'requires_approval' => true,
                    'approver_role'    => 'accounting',
                ],
                [
                    'name'             => 'Payment Verified',
                    'description'      => 'Payment has been verified and is now marked as paid.',
                    'requires_approval' => false,
                ],
            ],
        ]);

        $this->command->info('✅ Payment approval workflow created with approver role: accounting');
    }
}
