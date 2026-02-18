<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import type { BreadcrumbItem } from '@/types'

interface Props {
  admins: any
  stats?: any
}

defineProps<Props>()

const breadcrumbItems: BreadcrumbItem[] = [
  {
    title: 'Admin Management',
    href: route('admin.users.index'),
  },
]

const statusBadgeClass = (status: boolean) => {
  return status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
}

const adminTypeBadgeClass = (type: string) => {
  const classes: Record<string, string> = {
    super: 'bg-purple-100 text-purple-800',
    manager: 'bg-blue-100 text-blue-800',
    operator: 'bg-gray-100 text-gray-800',
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getAdminTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    super: 'Super Admin',
    manager: 'Manager',
    operator: 'Operator',
  }
  return labels[type] || type
}
</script>

<template>
  <Head title="Admin Users" />

  <AppLayout :breadcrumbs="breadcrumbItems">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Admin Users</h1>
            <p class="mt-2 text-gray-600">Manage administrator accounts and permissions</p>
          </div>
          <Link :href="route('admin.users.create')">
            <Button>+ Create Admin</Button>
          </Link>
        </div>

        <!-- Statistics -->
        <div v-if="stats" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Active Admins</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_active_admins }}</div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Super Admins</div>
            <div class="mt-2 text-3xl font-bold text-purple-600">{{ stats.super_admins }}</div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Managers</div>
            <div class="mt-2 text-3xl font-bold text-blue-600">{{ stats.managers }}</div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Operators</div>
            <div class="mt-2 text-3xl font-bold text-gray-600">{{ stats.operators }}</div>
          </div>
        </div>

        <!-- Admins Table -->
        <div class="bg-white overflow-hidden shadow-md rounded-lg">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terms</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="admin in admins.data" :key="admin.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-medium text-gray-900">{{ admin.last_name }}, {{ admin.first_name }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-gray-600">{{ admin.email }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="['px-3 py-1 rounded-full text-xs font-medium', adminTypeBadgeClass(admin.admin_type)]">
                      {{ getAdminTypeLabel(admin.admin_type) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-gray-600">{{ admin.department || '—' }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="['px-3 py-1 rounded-full text-xs font-medium', statusBadgeClass(admin.is_active)]">
                      {{ admin.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span v-if="admin.terms_accepted_at" class="text-green-600 text-sm">✓ Accepted</span>
                    <span v-else class="text-red-600 text-sm">✗ Pending</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <Link :href="route('admin.users.show', admin.id)">
                      <Button variant="ghost" size="sm">View</Button>
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="admins.links" class="px-6 py-4 bg-gray-50 border-t">
            <div class="flex justify-center space-x-2">
              <Link
                v-for="link in admins.links"
                :key="link.label"
                :href="link.url || '#'"
                :class="['px-3 py-2 rounded', link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border']"
                v-html="link.label"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
