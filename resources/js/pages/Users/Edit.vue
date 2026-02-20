<template>
  <div class="w-full p-6">
    <!-- Breadcrumbs -->
    <Breadcrumbs :items="breadcrumbs" />

    <!-- Page Heading -->
    <h1 class="text-2xl font-bold mb-6">Edit User: {{ user.name }}</h1>

    <!-- Form -->
    <form @submit.prevent="submit" class="bg-white shadow-md rounded-xl p-6 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
        <input v-model="form.name" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
        <input v-model="form.email" type="email" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">New Password (leave blank to keep current)</label>
        <input v-model="form.password" type="password" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
        <input v-model="form.password_confirmation" type="password" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
        <select v-model="form.role" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
          <option v-for="role in userRoles" :key="role" :value="role">
            {{ role.charAt(0).toUpperCase() + role.slice(1).replace('_', ' ') }}
          </option>
        </select>
      </div>

      <div class="flex justify-end gap-3 pt-4">
        <button type="button" @click="$page.props.auth?.user && $router.back()" class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-50">
          Cancel
        </button>
        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
          Update User
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Breadcrumbs from '@/components/Breadcrumbs.vue'

const { user, userRoles } = defineProps<{
  user: any
  userRoles: any[]
  message: string
}>()

const form = useForm({
  name: user.name,
  email: user.email,
  password: '',
  password_confirmation: '',
  role: user.role
})

const breadcrumbs = [
  { title: 'Dashboard', href: route('dashboard') },
  { title: 'Users', href: route('users.index') },
  { title: `Edit ${user.name}`, href: '#' }
]

function submit() {
  form.put(route('users.update', user.id))
}
</script>