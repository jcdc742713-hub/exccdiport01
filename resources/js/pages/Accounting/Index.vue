<template>
  <Head title="Accounting Workflows" />

  <AppLayout>
    <div class="space-y-6 w-full p-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Accounting Workflows</h1>
          <p class="text-gray-500">Manage transaction and payment workflows</p>
        </div>
        <button
          @click="$router.visit(route('accounting-workflows.create'))"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
        >
          + New Workflow
        </button>
      </div>

      <!-- Filters -->
      <div class="flex gap-3 mb-6">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search by reference, student, or type..."
          class="flex-1 p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
        />
        <select
          v-model="statusFilter"
          class="p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
        >
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>

      <!-- Workflows Table -->
      <div class="border rounded-xl shadow-sm bg-white overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-100 border-b">
            <tr class="text-left text-sm font-semibold text-gray-700">
              <th class="p-4">Reference</th>
              <th class="p-4">Type</th>
              <th class="p-4">Student</th>
              <th class="p-4">Amount</th>
              <th class="p-4">Status</th>
              <th class="p-4">Created</th>
              <th class="p-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="workflow in filteredWorkflows"
              :key="workflow.id"
              class="border-b hover:bg-gray-50 transition-colors text-sm"
            >
              <td class="p-4 font-mono">{{ workflow.reference }}</td>
              <td class="p-4">{{ workflow.type }}</td>
              <td class="p-4">
                <div>
                  <p class="font-medium">{{ workflow.student_name }}</p>
                  <p class="text-xs text-gray-500">{{ workflow.student_id }}</p>
                </div>
              </td>
              <td class="p-4 font-semibold">₱{{ formatCurrency(workflow.amount) }}</td>
              <td class="p-4">
                <span
                  :class="{
                    'bg-yellow-100 text-yellow-800': workflow.status === 'pending',
                    'bg-blue-100 text-blue-800': workflow.status === 'in_progress',
                    'bg-green-100 text-green-800': workflow.status === 'approved',
                    'bg-red-100 text-red-800': workflow.status === 'rejected',
                  }"
                  class="px-3 py-1 rounded-full text-xs font-semibold"
                >
                  {{ workflow.status }}
                </span>
              </td>
              <td class="p-4">{{ formatDate(workflow.created_at) }}</td>
              <td class="p-4">
                <button
                  @click="$router.visit(route('accounting-workflows.show', workflow.id))"
                  class="px-3 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors"
                >
                  View
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-if="filteredWorkflows.length === 0" class="p-12 text-center">
          <p class="text-gray-500 text-lg">No workflows found</p>
          <p class="text-sm text-gray-400 mt-2">Try adjusting your filters or search criteria</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'

interface Workflow {
  id: number
  reference: string
  type: string
  student_name: string
  student_id: string
  amount: number
  status: string
  created_at: string
}

interface Props {
  workflows: Workflow[]
}

const props = defineProps<Props>()

const searchQuery = ref('')
const statusFilter = ref('')

const breadcrumbs = [
  { title: 'Dashboard', href: route('accounting.dashboard') },
  { title: 'Accounting Workflows' },
]

const filteredWorkflows = computed(() => {
  let filtered = props.workflows

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(w =>
      w.reference.toLowerCase().includes(query) ||
      w.student_name.toLowerCase().includes(query) ||
      w.student_id.toLowerCase().includes(query) ||
      w.type.toLowerCase().includes(query)
    )
  }

  if (statusFilter.value) {
    filtered = filtered.filter(w => w.status === statusFilter.value)
  }

  return filtered
})

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>
