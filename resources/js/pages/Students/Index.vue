<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'

const props = defineProps<{
  students: any
  filters: { search?: string; status?: string }
  auth: { user: { role: string } }
}>()

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')

// Debounced search
let timeout: number
watch([search, statusFilter], (values) => {
  clearTimeout(timeout)
  timeout = setTimeout(() => {
    router.get(
      route('students.index'),
      { search: search.value, status: statusFilter.value },
      { preserveState: true, replace: true }
    )
  }, 300)
})

const breadcrumbs = [
  { title: 'Dashboard', href: route('dashboard') },
  { title: 'Students' }
]

const formatDate = (date: string | null) => {
  return date ? new Date(date).toLocaleDateString() : '-'
}
</script>

<template>
  <Head title="Students Archive" />

  <AppLayout>
    <div class="px-6 py-4">
      <!-- Header -->
      <Breadcrumbs :items="breadcrumbs" />
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Students</h1>
        <Link
          :href="route('students.create')"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors mt-4 sm:mt-0"
        >
          ➕ Add Student
        </Link>
      </div>

      <!-- Filters -->
      <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <input
          v-model="search"
          type="text"
          placeholder="Search students..."
          class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <select
          v-model="statusFilter"
          class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="active">Active</option>
          <option value="suspended">Suspended</option>
          <option value="graduated">Graduated</option>
        </select>
      </div>

      <!-- Students Table -->
      <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Student Number</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Enrollment Date</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr v-for="student in students.data" :key="student.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 text-sm text-gray-700">{{ student.student_number }}</td>
              <td class="px-6 py-4 text-sm font-medium text-gray-900">
                {{ student.first_name }} {{ student.last_name }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">{{ student.email }}</td>
              <td class="px-6 py-4 text-sm font-semibold capitalize">
                <span
                  :class="[
                    'status-badge',
                    `status-${student.enrollment_status?.toLowerCase()}`
                  ]"
                >
                  {{ student.enrollment_status || '-' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">{{ formatDate(student.enrollment_date) }}</td>
              <td class="px-6 py-4 text-sm flex gap-3">
                <!-- View -->
                <Link :href="`/students/${student.id}`" class="text-blue-600 hover:text-blue-800">
                  View
                </Link>

                <!-- Edit (only for admins/super_admins) -->
                <Link
                  v-if="['admin', 'super_admin'].includes(props.auth.user.role)"
                  :href="`/students/${student.id}/edit`"
                  class="text-green-600 hover:text-green-800"
                >
                  Edit
                </Link>
              </td>
            </tr>
            <tr v-if="students.data.length === 0">
              <td colspan="6" class="px-6 py-6 text-center text-gray-500">No students found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="flex justify-center mt-6 space-x-2">
        <Link
          v-for="link in students.links"
          :key="link.label"
          :href="link.url || '#'"
          class="px-4 py-2 text-sm border rounded-lg transition-colors"
          :class="{
            'bg-blue-600 text-white border-blue-600': link.active,
            'text-gray-600 hover:bg-gray-100': !link.active,
            'cursor-not-allowed opacity-50': !link.url
          }"
          v-html="link.label"
        />
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  display: inline-block;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-active {
  background: #d1fae5;
  color: #065f46;
}

.status-suspended {
  background: #fee2e2;
  color: #991b1b;
}

.status-graduated {
  background: #dbeafe;
  color: #1e40af;
}
</style>