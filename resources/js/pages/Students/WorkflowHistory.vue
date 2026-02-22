<template>
  <Head title="Workflow History" />

  <AppLayout>
    <div class="space-y-6 w-full p-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold">Workflow History</h1>
        <p class="text-gray-500">View the approval workflow history for this student</p>
      </div>

      <!-- Timeline View -->
      <div class="space-y-4">
        <div v-if="workflows && workflows.length > 0">
          <div
            v-for="(workflow, index) in workflows"
            :key="workflow.id"
            class="relative"
          >
            <!-- Timeline Connector -->
            <div
              v-if="index < workflows.length - 1"
              class="absolute left-6 top-12 w-0.5 h-12 bg-gray-300"
            ></div>

            <!-- Timeline Item -->
            <div class="flex gap-4">
              <!-- Dot -->
              <div
                class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center font-bold text-white z-10"
                :class="{
                  'bg-green-500': workflow.status === 'approved',
                  'bg-red-500': workflow.status === 'rejected',
                  'bg-yellow-500': workflow.status === 'in_progress',
                  'bg-gray-400': workflow.status === 'pending',
                }"
              >
                {{ index + 1 }}
              </div>

              <!-- Content -->
              <div class="flex-1 border rounded-lg p-4 bg-white shadow-sm">
                <div class="flex items-start justify-between">
                  <div>
                    <h3 class="font-semibold text-lg">{{ workflow.workflow_type }}</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ workflow.description }}</p>
                  </div>
                  <span
                    :class="{
                      'bg-green-100 text-green-800': workflow.status === 'approved',
                      'bg-red-100 text-red-800': workflow.status === 'rejected',
                      'bg-yellow-100 text-yellow-800': workflow.status === 'in_progress',
                      'bg-gray-100 text-gray-800': workflow.status === 'pending',
                    }"
                    class="px-3 py-1 rounded-full text-sm font-semibold"
                  >
                    {{ workflow.status }}
                  </span>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-4 text-sm">
                  <div>
                    <p class="text-gray-600">Started</p>
                    <p class="font-semibold">{{ formatDate(workflow.created_at) }}</p>
                  </div>
                  <div>
                    <p class="text-gray-600">{{ workflow.status === 'approved' || workflow.status === 'rejected' ? 'Completed' : 'Updated' }}</p>
                    <p class="font-semibold">{{ formatDate(workflow.updated_at) }}</p>
                  </div>
                </div>

                <div v-if="workflow.approver_name" class="mt-4 pt-4 border-t">
                  <p class="text-sm text-gray-600">Approved by</p>
                  <p class="font-semibold">{{ workflow.approver_name }}</p>
                </div>

                <div v-if="workflow.comment" class="mt-4">
                  <p class="text-sm text-gray-600">Comments</p>
                  <p class="mt-1 p-3 bg-gray-50 rounded text-sm">{{ workflow.comment }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12 border rounded-lg bg-gray-50">
          <p class="text-gray-500 text-lg">No workflow history available</p>
          <p class="text-sm text-gray-400 mt-2">This student has not been through any approval workflows yet.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'

interface Workflow {
  id: number
  workflow_type: string
  description: string
  status: string
  approver_name: string | null
  comment: string | null
  created_at: string
  updated_at: string
}

interface Props {
  student_id: string
  workflows: Workflow[]
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Dashboard', href: route('admin.dashboard') },
  { title: 'Students', href: route('students.index') },
  { title: `${props.student_id} - Workflow History` },
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
