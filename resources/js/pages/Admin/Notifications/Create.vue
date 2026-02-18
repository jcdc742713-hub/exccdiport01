<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import Form from './Form.vue'

interface Props {
  // No props needed for create
}

defineProps<Props>()
</script>

<template>
  <Head title="Create Notification" />
  <Form />
</template>
