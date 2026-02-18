<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import type { BreadcrumbItem } from '@/types'

interface Props {
  admin: any
}

defineProps<Props>()

const breadcrumbItems: BreadcrumbItem[] = [
  {
    title: 'Admin Management',
    href: route('admin.users.index'),
  },
  {
    title: `${props.admin.last_name}, ${props.admin.first_name}`,
    href: route('admin.users.show', props.admin.id),
  },
]

const getAdminTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    super: 'Super Admin',
    manager: 'Manager',
    operator: 'Operator',
  }
  return labels[type] || type
}

const adminTypeBadgeClass = (type: string) => {
  const classes: Record<string, string> = {
    super: 'bg-purple-100 text-purple-800',
    manager: 'bg-blue-100 text-blue-800',
    operator: 'bg-gray-100 text-gray-800',
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const statusBadgeClass = (status: boolean) => {
  return status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
}
</script>

<template>
  <Head :title="`Admin: ${admin.last_name}, ${admin.first_name}`" />

  <AppLayout :breadcrumbs="breadcrumbItems">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-md rounded-lg p-6">
          <div class="flex justify-between items-start">
            <div>
              <h1 class="text-3xl font-bold text-gray-900">
                {{ admin.last_name }}, {{ admin.first_name }}
              </h1>
              <p class="text-gray-600 mt-2">{{ admin.email }}</p>
            </div>
            <div class="flex space-x-2">
              <Link :href="route('admin.users.edit', admin.id)">
                <Button>Edit</Button>
              </Link>
              <Button
                v-if="admin.is_active"
                variant="destructive"
                @click="deactivate"
              >
                Deactivate
              </Button>
              <Button
                v-else
                variant="outline"
                @click="reactivate"
              >
                Reactivate
              </Button>
            </div>
          </div>
        </div>

        <!-- Admin Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Info Card -->
          <div class="bg-white overflow-hidden shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Admin Information</h2>
            <dl class="space-y-4">
              <div>
                <dt class="text-sm font-medium text-gray-600">Admin Type</dt>
                <dd class="mt-1">
                  <span :class="['px-3 py-1 rounded-full text-sm font-medium', adminTypeBadgeClass(admin.admin_type)]">
                    {{ getAdminTypeLabel(admin.admin_type) }}
                  </span>
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-600">Department</dt>
                <dd class="mt-1 text-gray-900">{{ admin.department || '—' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-600">Status</dt>
                <dd class="mt-1">
                  <span :class="['px-3 py-1 rounded-full text-sm font-medium', statusBadgeClass(admin.is_active)]">
                    {{ admin.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </dd>
              </div>
            </dl>
          </div>

          <!-- Account Details -->
          <div class="bg-white overflow-hidden shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Details</h2>
            <dl class="space-y-4">
              <div>
                <dt class="text-sm font-medium text-gray-600">User ID</dt>
                <dd class="mt-1 text-gray-900">{{ admin.id }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-600">Created Date</dt>
                <dd class="mt-1 text-gray-900">{{ new Date(admin.created_at).toLocaleDateString() }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-600">Last Updated</dt>
                <dd class="mt-1 text-gray-900">{{ new Date(admin.updated_at).toLocaleDateString() }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-600">Last Login</dt>
                <dd class="mt-1 text-gray-900">
                  {{ admin.last_login_at ? new Date(admin.last_login_at).toLocaleDateString() : '—' }}
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Audit Information -->
        <div class="bg-white overflow-hidden shadow-md rounded-lg p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Audit Information</h2>
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <dt class="text-sm font-medium text-gray-600">Created By</dt>
              <dd class="mt-1 text-gray-900">
                <span v-if="admin.createdByUser">
                  {{ admin.createdByUser.last_name }}, {{ admin.createdByUser.first_name }}
                </span>
                <span v-else class="text-gray-400">System</span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-600">Updated By</dt>
              <dd class="mt-1 text-gray-900">
                <span v-if="admin.updatedByUser">
                  {{ admin.updatedByUser.last_name }}, {{ admin.updatedByUser.first_name }}
                </span>
                <span v-else class="text-gray-400">Never updated</span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-600">Terms Accepted</dt>
              <dd class="mt-1 text-gray-900">
                <span v-if="admin.terms_accepted_at" class="text-green-600">
                  ✓ {{ new Date(admin.terms_accepted_at).toLocaleDateString() }}
                </span>
                <span v-else class="text-red-600">✗ Not accepted</span>
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
export default {
  methods: {
    deactivate() {
      if (confirm('Are you sure you want to deactivate this admin?')) {
        this.$inertia.post(route('admin.users.deactivate', this.admin.id))
      }
    },
    reactivate() {
      this.$inertia.post(route('admin.users.reactivate', this.admin.id))
    },
  },
}
</script>
