<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import type { BreadcrumbItem } from '@/types'
import { ArrowLeft } from 'lucide-vue-next'
import { ref, computed } from 'vue'

interface Props {
  notification?: {
    id: number
    title: string
    message: string
    target_role: string
    start_date: string
    end_date: string
  }
}

const props = withDefaults(defineProps<Props>(), {
  notification: undefined,
})

const isEditing = computed(() => !!props.notification?.id)

const breadcrumbItems: BreadcrumbItem[] = [
  { title: 'Admin Dashboard', href: '/admin/dashboard' },
  { title: 'Notifications', href: '/notifications' },
  { 
    title: isEditing.value ? 'Edit Notification' : 'Create Notification', 
    href: '#' 
  },
]

const form = useForm({
  title: props.notification?.title || '',
  message: props.notification?.message || '',
  target_role: props.notification?.target_role || 'student',
  start_date: props.notification?.start_date || '',
  end_date: props.notification?.end_date || '',
})

const submit = () => {
  if (isEditing.value && props.notification?.id) {
    form.put(`/notifications/${props.notification.id}`)
  } else {
    form.post('/notifications')
  }
}

const roleOptions = [
  { value: 'student', label: 'Students' },
  { value: 'accounting', label: 'Accounting Staff' },
  { value: 'admin', label: 'Admins' },
  { value: 'all', label: 'Everyone' },
]

const messages = {
  student: 'This notification will be sent to all students. Use this to remind them about payment dues.',
  accounting: 'This notification will be sent to accounting staff. Use this for accounting-related announcements.',
  admin: 'This notification will be sent to admin users. Use this for administrative announcements.',
  all: 'This notification will be sent to all users in the system.',
}
</script>

<template>
  <Head :title="isEditing ? 'Edit Notification' : 'Create Notification'" />

  <AppLayout :breadcrumbs="breadcrumbItems">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex items-center gap-4">
          <Link :href="'/notifications'">
            <Button variant="ghost" size="icon">
              <ArrowLeft class="w-4 h-4" />
            </Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              {{ isEditing ? 'Edit Notification' : 'Create Payment Notification' }}
            </h1>
            <p class="text-gray-600 mt-1">
              {{ isEditing ? 'Update notification details' : 'Set up a new payment notification for users' }}
            </p>
          </div>
        </div>

        <!-- Form Card -->
        <Card>
          <CardHeader>
            <CardTitle>Notification Details</CardTitle>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Title Field -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Title *
                </label>
                <input
                  v-model="form.title"
                  type="text"
                  placeholder="e.g., Second Semester Tuition Payment Reminder"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required
                />
                <p v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</p>
              </div>

              <!-- Message Field -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Message
                </label>
                <textarea
                  v-model="form.message"
                  placeholder="Enter the notification message. Include payment amount, deadline, and payment instructions."
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent h-32 resize-none"
                ></textarea>
                <p v-if="form.errors.message" class="text-red-600 text-sm mt-1">{{ form.errors.message }}</p>
              </div>

              <!-- Target Role -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Target Audience *
                </label>
                <select
                  v-model="form.target_role"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required
                >
                  <option v-for="option in roleOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
                <p class="text-xs text-gray-500 mt-2">
                  {{ messages[form.target_role as keyof typeof messages] }}
                </p>
                <p v-if="form.errors.target_role" class="text-red-600 text-sm mt-1">{{ form.errors.target_role }}</p>
              </div>

              <!-- Date Range -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Start Date *
                  </label>
                  <input
                    v-model="form.start_date"
                    type="date"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                  />
                  <p class="text-xs text-gray-500 mt-1">When the notification becomes active</p>
                  <p v-if="form.errors.start_date" class="text-red-600 text-sm mt-1">{{ form.errors.start_date }}</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    End Date (Optional)
                  </label>
                  <input
                    v-model="form.end_date"
                    type="date"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                  <p class="text-xs text-gray-500 mt-1">Leave empty for ongoing notifications</p>
                  <p v-if="form.errors.end_date" class="text-red-600 text-sm mt-1">{{ form.errors.end_date }}</p>
                </div>
              </div>

              <!-- Info Box -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-blue-900 mb-2">💡 Best Practices</h4>
                <ul class="text-sm text-blue-800 space-y-1">
                  <li>• Include specific payment amount and deadline in the message</li>
                  <li>• Provide payment method options and contact information</li>
                  <li>• Set appropriate start/end dates for the notification period</li>
                  <li>• Use clear, professional language</li>
                </ul>
              </div>

              <!-- Actions -->
              <div class="flex justify-end gap-3 pt-6 border-t">
                <Link :href="route('notifications.index')">
                  <Button type="button" variant="outline">
                    Cancel
                  </Button>
                </Link>
                <Button type="submit" :disabled="form.processing">
                  {{ form.processing ? 'Saving...' : (isEditing ? 'Update Notification' : 'Create Notification') }}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <!-- Preview Card -->
        <Card class="mt-8">
          <CardHeader>
            <CardTitle>Preview</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="border rounded-lg p-6 bg-gray-50">
              <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ form.title || 'Notification Title' }}</h3>
                <p class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">
                  {{ form.target_role }}
                </p>
              </div>

              <p class="text-gray-700 mb-4 whitespace-pre-wrap">{{ form.message || 'Your notification message will appear here...' }}</p>

              <div class="text-xs text-gray-600 space-y-1">
                <p v-if="form.start_date"><strong>Active from:</strong> {{ form.start_date }}</p>
                <p v-if="form.end_date"><strong>Until:</strong> {{ form.end_date }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
