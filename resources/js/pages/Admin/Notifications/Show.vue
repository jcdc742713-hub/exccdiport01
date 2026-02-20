<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import type { BreadcrumbItem } from '@/types'
import { ArrowLeft, Edit2, Calendar, Users } from 'lucide-vue-next'

interface Notification {
  id: number
  title: string
  message: string
  target_role: string
  start_date: string
  end_date: string
  is_active: boolean
  created_at: string
  updated_at: string
}

interface Props {
  notification: Notification
}

const props = withDefaults(defineProps<Props>(), {
  notification: () => ({
    id: 0,
    title: '',
    message: '',
    target_role: 'student',
    start_date: '',
    end_date: '',
    created_at: '',
    updated_at: '',
  }),
})

const breadcrumbItems: BreadcrumbItem[] = [
  { title: 'Admin Dashboard', href: '/admin/dashboard' },
  { title: 'Notifications', href: '/notifications' },
  { title: 'Notification Details', href: '#' },
]

const getRoleColor = (role: string) => {
  const colors: Record<string, string> = {
    student: 'bg-blue-100 text-blue-800',
    accounting: 'bg-purple-100 text-purple-800',
    admin: 'bg-orange-100 text-orange-800',
    all: 'bg-green-100 text-green-800',
  }
  return colors[role] || 'bg-gray-100 text-gray-800'
}

const isActive = () => {
  // Check if the notification is explicitly marked as active and within date range
  if (!props.notification.is_active) return false
  
  const today = new Date()
  const startDate = new Date(props.notification.start_date)
  const endDate = props.notification.end_date ? new Date(props.notification.end_date) : null
  
  const isStarted = startDate <= today
  const isEnded = endDate ? endDate < today : false
  
  return isStarted && !isEnded
}
</script>

<template>
  <Head title="Notification Details" />

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
            <h1 class="text-3xl font-bold text-gray-900">{{ notification.title }}</h1>
            <div class="mt-2 flex items-center gap-2">
              <span :class="['px-3 py-1 rounded-full text-xs font-medium', isActive() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800']">
                {{ isActive() ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Content Card -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle>Notification Details</CardTitle>
          </CardHeader>
          <CardContent class="space-y-6">
            <!-- Status -->
            <div>
              <h3 class="text-sm font-medium text-gray-700 mb-2">Status</h3>
              <div class="flex items-center gap-3">
                <span :class="['inline-block px-3 py-1 rounded-full text-sm font-medium', props.notification.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800']">
                  {{ props.notification.is_active ? '✓ Enabled' : '○ Disabled' }}
                </span>
                <span v-if="props.notification.is_active" :class="['px-3 py-1 rounded-full text-xs font-medium', isActive() ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800']">
                  {{ isActive() ? 'Currently Active' : 'Not Yet Active' }}
                </span>
              </div>
            </div>

            <hr />

            <!-- Message -->
            <div>
              <h3 class="text-sm font-medium text-gray-700 mb-2">Message</h3>
              <p class="text-gray-900 whitespace-pre-wrap">{{ notification.message || 'No message provided' }}</p>
            </div>

            <hr />

            <!-- Target Audience -->
            <div>
              <h3 class="text-sm font-medium text-gray-700 mb-2">Target Audience</h3>
              <span :class="['inline-block px-3 py-1 rounded-full text-sm font-medium', getRoleColor(notification.target_role)]">
                {{ notification.target_role.charAt(0).toUpperCase() + notification.target_role.slice(1) }}
              </span>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <h3 class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                  <Calendar class="w-4 h-4" />
                  Start Date
                </h3>
                <p class="text-gray-900">{{ new Date(notification.start_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
              </div>

              <div v-if="notification.end_date">
                <h3 class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                  <Calendar class="w-4 h-4" />
                  End Date
                </h3>
                <p class="text-gray-900">{{ new Date(notification.end_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Timestamps -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle class="text-base">Timeline</CardTitle>
          </CardHeader>
          <CardContent class="space-y-2 text-sm text-gray-600">
            <p><strong>Created:</strong> {{ new Date(notification.created_at).toLocaleString() }}</p>
            <p><strong>Updated:</strong> {{ new Date(notification.updated_at).toLocaleString() }}</p>
          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
          <Link :href="'/notifications'">
            <Button variant="outline">Back to Notifications</Button>
          </Link>
          <Link :href="`/notifications/${notification.id}/edit`">
            <Button>
              <Edit2 class="w-4 h-4 mr-2" />
              Edit Notification
            </Button>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
