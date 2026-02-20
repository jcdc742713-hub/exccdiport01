<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Bell } from 'lucide-vue-next'

interface Props {
  title?: string
  message?: string
  startDate?: string
  endDate?: string
  targetRole?: string
  selectedStudentEmail?: string
}

withDefaults(defineProps<Props>(), {
  title: 'Notification Title',
  message: 'Your message will appear here...',
  startDate: '',
  endDate: '',
  targetRole: 'student',
  selectedStudentEmail: '',
})

const getRoleLabel = (role: string) => {
  const labels: Record<string, string> = {
    student: 'All Students',
    accounting: 'Accounting Staff',
    admin: 'Admins',
    all: 'Everyone',
  }
  return labels[role] || role
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle class="text-sm">📺 Preview</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="border-2 border-gray-200 rounded-lg p-4 bg-gradient-to-b from-gray-50 to-white">
        <div class="space-y-3">
          <!-- Title Section -->
          <div class="flex items-center gap-2">
            <Bell class="w-5 h-5 text-blue-600" />
            <h4 class="font-semibold text-gray-900 text-sm">
              {{ title }}
            </h4>
          </div>

          <!-- Message Section -->
          <p class="text-xs text-gray-700 leading-relaxed whitespace-pre-wrap max-h-32 overflow-y-auto">
            {{ message }}
          </p>

          <!-- Metadata Section -->
          <div class="text-xs text-gray-500 space-y-1 pt-2 border-t border-gray-200">
            <p v-if="startDate"><strong>📅 From:</strong> {{ startDate }}</p>
            <p v-if="endDate"><strong>📅 Until:</strong> {{ endDate }}</p>
            <p v-if="selectedStudentEmail"><strong>👤 For:</strong> {{ selectedStudentEmail }}</p>
            <p v-else><strong>👥 For:</strong> {{ getRoleLabel(targetRole) }}</p>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
