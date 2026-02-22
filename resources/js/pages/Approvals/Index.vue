<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog'

interface WorkflowMeta {
    transaction_id: number
    amount: number
    payment_method: string
    term_name: string
    student_user_id: number
    submitted_at: string
}

interface Approval {
    id: number
    step_name: string
    status: 'pending' | 'approved' | 'rejected'
    comments: string | null
    created_at: string
    workflow_instance: {
        metadata: WorkflowMeta
        workflow: { name: string }
        workflowable: {
            reference: string
            amount: number
            user?: { first_name: string; last_name: string; student_id: string }
        }
    }
}

const props = defineProps<{
    approvals: { data: Approval[]; links: any[] }
    filters: { status?: string }
}>()

const breadcrumbs = [
    { title: 'Dashboard', href: route('accounting.dashboard') },
    { title: 'Payment Approvals', href: route('approvals.index') },
]

const filters = ref({ ...props.filters })
const showRejectDialog = ref(false)
const selectedApprovalId = ref<number | null>(null)

const rejectForm = useForm({ comments: '' })

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount)

const formatDate = (date: string) =>
    new Date(date).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })

const formatMethod = (method: string) => ({
    cash: 'Cash', 
    gcash: 'GCash', 
    bank_transfer: 'Bank Transfer',
    credit_card: 'Credit Card', 
    debit_card: 'Debit Card',
}[method] ?? method)

const applyFilter = () => {
    router.get(route('approvals.index'), filters.value, { preserveState: true, replace: true })
}

const approve = (approvalId: number) => {
    router.post(route('approvals.approve', approvalId), {}, {
        preserveScroll: true,
        onSuccess: () => router.reload(),
    })
}

const openRejectDialog = (approvalId: number) => {
    selectedApprovalId.value = approvalId
    rejectForm.reset()
    showRejectDialog.value = true
}

const submitRejection = () => {
    if (!selectedApprovalId.value) return
    rejectForm.post(route('approvals.reject', selectedApprovalId.value), {
        onSuccess: () => {
            showRejectDialog.value = false
            router.reload()
        },
    })
}

const getStudentName = (approval: Approval) => {
    const w = approval.workflow_instance.workflowable
    if (w.user) return `${w.user.last_name}, ${w.user.first_name}`
    return 'Unknown Student'
}
</script>

<template>
    <Head title="Payment Approvals" />
    <AppLayout>
        <div class="w-full p-6 space-y-6">
            <Breadcrumbs :items="breadcrumbs" />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Payment Approvals</h1>
                    <p class="text-gray-500">Review and verify student payment submissions</p>
                </div>
                <select
                    v-model="filters.status"
                    @change="applyFilter"
                    class="border rounded-lg px-3 py-2 text-sm"
                >
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div v-if="approvals.data.length === 0" class="text-center py-16 text-gray-400">
                No approvals found.
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="approval in approvals.data"
                    :key="approval.id"
                    class="border rounded-xl bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1 flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-lg">{{ getStudentName(approval) }}</h3>
                                <span
                                    class="text-xs font-semibold px-2 py-1 rounded-full"
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': approval.status === 'pending',
                                        'bg-green-100 text-green-800': approval.status === 'approved',
                                        'bg-red-100 text-red-800': approval.status === 'rejected',
                                    }"
                                >{{ approval.status }}</span>
                            </div>
                            <p class="text-sm text-gray-500">
                                Ref: <span class="font-mono">{{ approval.workflow_instance.workflowable.reference }}</span>
                            </p>
                        </div>
                        <p class="text-2xl font-bold text-blue-700 whitespace-nowrap">
                            {{ formatCurrency(approval.workflow_instance.metadata?.amount ?? approval.workflow_instance.workflowable.amount) }}
                        </p>
                    </div>

                    <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm text-gray-600">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Term</p>
                            <p>{{ approval.workflow_instance.metadata?.term_name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Method</p>
                            <p>{{ formatMethod(approval.workflow_instance.metadata?.payment_method ?? '') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Student ID</p>
                            <p>{{ approval.workflow_instance.workflowable.user?.student_id ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Submitted</p>
                            <p>{{ formatDate(approval.created_at) }}</p>
                        </div>
                    </div>

                    <div v-if="approval.comments" class="mt-3 p-3 bg-gray-50 rounded-lg text-sm text-gray-700">
                        <strong>Comments:</strong> {{ approval.comments }}
                    </div>

                    <div v-if="approval.status === 'pending'" class="mt-4 flex gap-3">
                        <Button @click="approve(approval.id)" class="bg-green-600 hover:bg-green-700 text-white">
                            ✓ Approve
                        </Button>
                        <Button variant="outline" @click="openRejectDialog(approval.id)" class="border-red-300 text-red-600 hover:bg-red-50">
                            ✗ Reject
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Dialog -->
        <Dialog v-model:open="showRejectDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reject Payment</DialogTitle>
                    <DialogDescription>Provide a reason. The student will be notified.</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 mt-2">
                    <textarea
                        v-model="rejectForm.comments"
                        class="w-full border rounded-lg p-3 text-sm min-h-[100px]"
                        placeholder="Enter rejection reason (required)..."
                    />
                    <p v-if="rejectForm.errors.comments" class="text-red-500 text-sm">{{ rejectForm.errors.comments }}</p>
                    <div class="flex justify-end gap-3">
                        <Button variant="outline" @click="showRejectDialog = false">Cancel</Button>
                        <Button
                            variant="destructive"
                            :disabled="rejectForm.processing || !rejectForm.comments"
                            @click="submitRejection"
                        >Confirm Rejection</Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>