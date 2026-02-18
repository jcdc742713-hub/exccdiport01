<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import Form from './Form.vue'

interface Notification {
  id: number
  title: string
  message: string
  target_role: string
  start_date: string
  end_date: string
}

interface Props {
  notification: Notification
}

defineProps<Props>()
</script>

<template>
  <Head title="Edit Notification" />
  <Form :notification="notification" />
</template>
