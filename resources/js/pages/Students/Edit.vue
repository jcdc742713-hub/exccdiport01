<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import Breadcrumbs from '@/components/Breadcrumbs.vue'

const { student } = defineProps<{
  student: any
}>()

const form = useForm({
  student_id: student.student_id,
  name: student.name,
  email: student.email,
  course: student.course,
  year_level: student.year_level,
  birthday: student.birthday ? student.birthday.split('T')[0] : '',
  phone: student.phone || '',
  address: student.address || '',
  total_balance: student.total_balance
})

const breadcrumbs = [
  { title: 'Dashboard', href: route('dashboard') },
  { title: 'Students', href: route('students.index') },
  { title: `Edit ${student.name}`, href: '#' }
]

function submit() {
  form.put(route('students.update', student.id))
}
</script>

<template>
  <Head :title="`Edit ${student.name}`" />

  <AppLayout>
    <div class="max-w-3xl mx-auto p-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Page Heading -->
      <h1 class="text-2xl font-semibold text-gray-800 mb-6">
        Edit Student: {{ student.name }}
      </h1>

      <!-- Form -->
      <form @submit.prevent="submit" class="bg-white shadow-md rounded-xl p-6 space-y-4">
        <!-- Student ID -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Student ID *</label>
          <input v-model="form.student_id" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
        </div>

        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
          <input v-model="form.name" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
        </div>

        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
          <input v-model="form.email" type="email" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
        </div>

        <!-- Course & Year Level -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Course *</label>
            <input v-model="form.course" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Year Level *</label>
            <input v-model="form.year_level" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>

        <!-- Total Balance -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Total Balance (₱) *</label>
          <input v-model="form.total_balance" type="number" step="0.01" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
        </div>

        <!-- Birthday -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Birthday</label>
          <input v-model="form.birthday" type="date" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
        </div>

        <!-- Phone -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
          <input v-model="form.phone" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
        </div>

        <!-- Address -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
          <textarea v-model="form.address" rows="3" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="$router.back()" class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-50">
            Cancel
          </button>
          <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
            Update Student
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>