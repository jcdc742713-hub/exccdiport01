<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AdminForm from './Form.vue'
import type { BreadcrumbItem } from '@/types'

interface Props {
  admin: any
  adminTypes: Array<{ value: string; label: string }>
}

const props = defineProps<Props>()

const breadcrumbItems: BreadcrumbItem[] = [
  {
    title: 'Admin',
    href: '/admin',
  },
  {
    title: 'Users',
    href: '/admin/users',
  },
  {
    title: `Edit: ${props.admin.last_name}, ${props.admin.first_name}`,
    href: `/admin/users/${props.admin.id}/edit`,
  },
]
</script>

<template>
  <Head title="Edit Admin User" />

  <AppLayout :breadcrumbs="breadcrumbItems">
    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-md rounded-lg p-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Admin User</h1>

          <div class="mb-4 p-4 bg-gray-50 rounded-lg">
            <div class="text-sm text-gray-600">
              <p><strong>Admin ID:</strong> {{ admin.id }}</p>
              <p><strong>Created:</strong> {{ new Date(admin.created_at).toLocaleDateString() }}</p>
              <p v-if="admin.updated_by"><strong>Last Updated:</strong> {{ new Date(admin.updated_at).toLocaleDateString() }}</p>
            </div>
          </div>

          <AdminForm :admin="admin" :is-editing="true" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
