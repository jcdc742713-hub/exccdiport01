<template>
  <Head title="Workflow Details" />

  <AppLayout>
    <div class="space-y-6 w-full p-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Workflow Details</h1>
          <p class="text-gray-500">View workflow information and approval status</p>
        </div>
      </div>

      <!-- Workflow Card -->
      <div v-if="workflow" class="border rounded-xl shadow-sm bg-white p-6 space-y-6">
        <!-- Status -->
        <div class="flex items-center gap-4">
          <span
            :class="{
              'bg-yellow-100 text-yellow-800': workflow.status === 'pending',
              'bg-green-100 text-green-800': workflow.status === 'approved',
              'bg-red-100 text-red-800': workflow.status === 'rejected',
              'bg-blue-100 text-blue-800': workflow.status === 'in_progress',
            }"
            class="px-4 py-2 rounded-full font-semibold text-sm"
          >
            {{ workflow.status }}
          </span>
        </div>

        <!-- Workflow Information -->
        <div class="grid grid-cols-3 gap-4 border-b pb-4">
          <div>
            <p class="text-sm text-gray-600">Workflow Type</p>
            <p class="font-semibold">{{ workflow.type }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Created</p>
            <p class="font-semibold">{{ formatDate(workflow.created_at) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Updated</p>
            <p class="font-semibold">{{ formatDate(workflow.updated_at) }}</p>
          </div>
        </div>

        <!-- Related Entity -->
        <div class="border-b pb-4">
          <p class="text-sm text-gray-600 mb-2">Related Entity</p>
          <div class="p-3 bg-gray-50 rounded-lg">
            <p class="text-sm"><strong>Type:</strong> {{ workflow.workflowable_type }}</p>
            <p class="text-sm mt-1"><strong>ID:</strong> {{ workflow.workflowable_id }}</p>
          </div>
        </div>

        <!-- Approvals -->
        <div v-if="workflow.approvals && workflow.approvals.length > 0" class="border-b pb-4">
          <p class="text-sm font-semibold mb-4">Approvals ({{ workflow.approvals.length }})</p>
          <div class="space-y-3">
            <div
              v-for="approval in workflow.approvals"
              :key="approval.id"
              class="p-3 border rounded-lg"
              :class="{
                'bg-green-50': approval.status === 'approved',
                'bg-red-50': approval.status === 'rejected',
                'bg-yellow-50': approval.status === 'pending',
              }"
            >
              <div class="flex items-start justify-between">
                <div>
                  <p class="font-semibold">{{ approval.approver_name }}</p>
                  <p class="text-sm text-gray-600">{{ formatDate(approval.created_at) }}</p>
                </div>
                <span
                  :class="{
                    'bg-green-100 text-green-800': approval.status === 'approved',
                    'bg-red-100 text-red-800': approval.status === 'rejected',
                    'bg-yellow-100 text-yellow-800': approval.status === 'pending',
                  }"
                  class="px-2 py-1 rounded text-xs font-semibold"
                >
                  {{ approval.status }}
                </span>
              </div>
              <p v-if="approval.comment" class="text-sm mt-2 text-gray-700">{{ approval.comment }}</p>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div v-if="workflow.description" class="pt-4">
          <p class="text-sm text-gray-600 mb-2">Description</p>
          <p class="text-gray-800">{{ workflow.description }}</p>
        </div>
      </div>

      <!-- Loading State -->
      <div v-else class="text-center py-12">
        <p class="text-gray-500">Loading workflow details...</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'

interface Approval {
  id: number
  status: string
  approver_name: string
  comment: string | null
  created_at: string
}

interface Workflow {
  id: number
  type: string
  status: string
  workflowable_type: string
  workflowable_id: number
  description: string | null
  approvals: Approval[]
  created_at: string
  updated_at: string
}

interface Props {
  workflow: Workflow
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Dashboard', href: route('admin.dashboard') },
  { title: 'Workflows', href: route('workflows.index') },
  { title: 'Details' },
]

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
