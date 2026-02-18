<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { useForm } from '@inertiajs/vue3'

interface Props {
  adminId?: number
  onTermsAccepted?: () => void
}

withDefaults(defineProps<Props>(), {})

const accepted = ref(false)
const termsVisible = ref(false)

const form = useForm({
  terms_accepted: false,
})

const submitTerms = () => {
  // In a real implementation, this would POST to accept terms
  accepted.value = true
  emit('termsAccepted')
}

const emit = defineEmits<{
  termsAccepted: []
}>()
</script>

<template>
  <div class="space-y-4">
    <div v-if="!accepted" class="border rounded-lg p-4 bg-amber-50">
      <h3 class="font-semibold text-lg mb-4">Terms & Conditions</h3>

      <div v-if="!termsVisible" class="mb-4">
        <p class="text-sm text-gray-600 mb-4">
          As an administrator, you must accept the terms and conditions before proceeding.
        </p>
        <Button
          @click="termsVisible = true"
          variant="outline"
          class="w-full"
        >
          Read Terms & Conditions
        </Button>
      </div>

      <div v-else class="bg-white border rounded p-4 mb-4 max-h-64 overflow-y-auto">
        <h4 class="font-semibold mb-2">Administrator Terms & Conditions</h4>
        <div class="text-sm space-y-2 text-gray-700">
          <p><strong>1. Responsibility:</strong> Administrators are responsible for maintaining system integrity.</p>
          <p><strong>2. Data Security:</strong> All user data must be handled securely and confidentially.</p>
          <p><strong>3. Audit Trail:</strong> All admin actions are logged and auditable.</p>
          <p><strong>4. Compliance:</strong> Administrators must comply with all system policies.</p>
          <p><strong>5. Account Security:</strong> You are responsible for protecting your login credentials.</p>
          <p><strong>6. Misuse:</strong> Unauthorized access or misuse of admin privileges is prohibited.</p>
        </div>
      </div>

      <div class="flex items-start space-x-2 mb-4">
        <Checkbox
          id="terms"
          v-model:checked="form.terms_accepted"
        />
        <label for="terms" class="text-sm cursor-pointer">
          I accept the terms and conditions
        </label>
      </div>

      <Button
        @click="submitTerms"
        :disabled="!form.terms_accepted || form.processing"
        class="w-full"
      >
        {{ form.processing ? 'Processing...' : 'Accept Terms' }}
      </Button>
    </div>

    <div v-else class="border-l-4 border-green-500 bg-green-50 p-4">
      <p class="text-green-800">
        ✓ Terms and conditions accepted
      </p>
    </div>
  </div>
</template>
