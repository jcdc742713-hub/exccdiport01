<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Trash2, Edit2, Plus, Bell, Calendar, Users } from 'lucide-vue-next'
import { ref, computed } from 'vue'

interface Notification {
  id: number
  title: string
  message: string
  type?: string
  target_role: string
  start_date: string
  end_date: string
  is_active: boolean
  created_at: string
  updated_at: string
}

interface Props {
  notifications: Notification[]
}

const props = withDefaults(defineProps<Props>(), {
  notifications: () => [],
})

const breadcrumbs = [
  { title: 'Admin', href: route('admin.dashboard') },
  { title: 'Notifications', href: route('notifications.index') },
]

const searchQuery = ref('')

const filteredNotifications = computed(() => {
  if (!searchQuery.value) return props.notifications
  return props.notifications.filter(notification =>
    notification.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    notification.message?.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const deleteNotification = (id: number) => {
  if (confirm('Are you sure you want to delete this notification?')) {
    router.delete(route('notifications.destroy', id))
  }
}

const getRoleColor = (role: string) => {
  const colors: Record<string, string> = {
    student: 'bg-blue-100 text-blue-800',
    accounting: 'bg-purple-100 text-purple-800',
    admin: 'bg-orange-100 text-orange-800',
    all: 'bg-green-100 text-green-800',
  }
  return colors[role] || 'bg-gray-100 text-gray-800'
}

const getTypeLabel = (type?: string) => {
  const labels: Record<string, string> = {
    general: '📢 General',
    payment_due: '💳 Payment Due',
    payment_approved: '✅ Approved',
    payment_rejected: '❌ Rejected',
  }
  return labels[type || 'general'] || 'General'
}

const getTypeColor = (type?: string) => {
  const colors: Record<string, string> = {
    general: 'bg-blue-100 text-blue-800',
    payment_due: 'bg-amber-100 text-amber-800',
    payment_approved: 'bg-emerald-100 text-emerald-800',
    payment_rejected: 'bg-red-100 text-red-800',
  }
  return colors[type || 'general'] || 'bg-gray-100 text-gray-800'
}

const isActive = (notification: Notification) => {
  if (!notification.is_active) return false

  const today = new Date()
  const startDate = new Date(notification.start_date)
  const endDate = notification.end_date ? new Date(notification.end_date) : null

  const isStarted = startDate <= today
  const isEnded = endDate ? endDate < today : false

  return isStarted && !isEnded
}
</script>

<template>
  <Head title="Payment Notifications" />

  <AppLayout>
    <div class="w-full p-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Payment Notifications</h1>
          <p class="text-gray-600">Create and manage payment due notifications for students</p>
        </div>
        <Link :href="route('notifications.create')">
          <Button>
            <Plus class="w-4 h-4 mr-2" />
            Create Notification
          </Button>
        </Link>
      </div>

      <!-- Search Bar -->
      <div class="mb-6">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search notifications..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <!-- Empty State -->
      <div v-if="filteredNotifications.length === 0" class="text-center py-12">
        <Bell class="w-12 h-12 mx-auto mb-4 text-gray-300" />
        <h3 class="text-lg font-semibold text-gray-700 mb-2">No notifications found</h3>
        <p class="text-gray-600 mb-4">
          {{ searchQuery ? 'Try adjusting your search' : 'Create your first notification to get started' }}
        </p>
        <Link v-if="!searchQuery" :href="route('notifications.create')">
          <Button variant="outline">
            <Plus class="w-4 h-4 mr-2" />
            Create First Notification
          </Button>
        </Link>
      </div>

      <!-- Notifications List -->
      <div v-else class="space-y-4">
        <Card v-for="notification in filteredNotifications" :key="notification.id">
          <CardContent class="pt-6">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="text-lg font-semibold text-gray-900">{{ notification.title }}</h3>
                  <span v-if="isActive(notification)" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Active
                  </span>
                  <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    Inactive
                  </span>
                </div>

                <p v-if="notification.message" class="text-gray-700 mb-3">{{ notification.message }}</p>

                <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                  <div class="flex items-center gap-1">
                    <Users class="w-4 h-4" />
                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getRoleColor(notification.target_role)]">
                      {{ notification.target_role.charAt(0).toUpperCase() + notification.target_role.slice(1) }}
                    </span>
                  </div>

                  <div v-if="notification.type" class="flex items-center gap-1">
                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getTypeColor(notification.type)]">
                      {{ getTypeLabel(notification.type) }}
                    </span>
                  </div>

                  <div class="flex items-center gap-1">
                    <Calendar class="w-4 h-4" />
                    <span>
                      {{ new Date(notification.start_date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                      }) }}
                    </span>
                  </div>

                  <div v-if="notification.end_date" class="flex items-center gap-1">
                    <Calendar class="w-4 h-4" />
                    <span>
                      to {{ new Date(notification.end_date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                      }) }}
                    </span>
                  </div>

                  <div class="flex items-center gap-1 ml-auto text-xs text-gray-500">
                    Created {{ new Date(notification.created_at).toLocaleDateString() }}
                  </div>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t">
              <Link :href="route('notifications.edit', notification.id)" as="button">
                <Button variant="outline" size="sm">
                  <Edit2 class="w-4 h-4 mr-2" />
                  Edit
                </Button>
              </Link>
              <button @click="deleteNotification(notification.id)">
                <Button variant="outline" size="sm" class="text-red-600 hover:text-red-700">
                  <Trash2 class="w-4 h-4 mr-2" />
                  Delete
                </Button>
              </button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>