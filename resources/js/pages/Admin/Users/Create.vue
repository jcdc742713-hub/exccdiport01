<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import AdminForm from './Form.vue'

interface Props {
  adminTypes: Array<{ value: string; label: string }>
}

defineProps<Props>()

const breadcrumbs = [
  { title: 'Admin', href: route('admin.dashboard') },
  { title: 'Users', href: route('users.index') },
  { title: 'Create New User', href: route('users.create') },
]
</script>

<template>
  <Head title="Create Admin User" />

  <AppLayout>
    <div class="w-full p-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="max-w-2xl">
        <div class="bg-white overflow-hidden shadow-md rounded-lg p-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-6">Create New Admin User</h1>

          <div class="text-sm text-gray-600 mb-6 p-4 bg-blue-50 rounded-lg">
            <strong>Note:</strong> New admin accounts require acceptance of Terms & Conditions before the account is fully activated.
          </div>

          <AdminForm :is-editing="false" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>