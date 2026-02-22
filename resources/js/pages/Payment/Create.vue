<template>
  <Head title="Make Payment" />

  <AppLayout>
    <div class="space-y-6 w-full p-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold">Make Payment</h1>
        <p class="text-gray-500">Submit your payment and track its status</p>
      </div>

      <!-- Payment Information -->
      <div class="grid grid-cols-2 gap-6">
        <!-- Outstanding Balance -->
        <div class="border rounded-xl shadow-sm bg-white p-6">
          <p class="text-sm text-gray-600">Outstanding Balance</p>
          <p class="text-4xl font-bold text-red-600 mt-2">₱{{ formatCurrency(outstandingBalance) }}</p>
        </div>

        <!-- Payment Method -->
        <div class="border rounded-xl shadow-sm bg-white p-6">
          <p class="text-sm text-gray-600">Payment Method</p>
          <p class="text-lg font-semibold mt-2">{{ paymentMethod || 'Select a method' }}</p>
        </div>
      </div>

      <!-- Payment Form -->
      <form @submit.prevent="submitPayment" class="border rounded-xl shadow-sm bg-white p-6 space-y-6">
        <!-- Student Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Student Name</label>
          <input
            type="text"
            :value="studentName"
            disabled
            class="w-full p-3 border rounded-lg bg-gray-50 text-gray-600"
          />
        </div>

        <!-- Amount -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Payment Amount</label>
          <div class="relative">
            <span class="absolute left-3 top-3 text-gray-600">₱</span>
            <input
              v-model.number="form.amount"
              type="number"
              step="0.01"
              min="0"
              :max="outstandingBalance"
              :placeholder="formatCurrency(outstandingBalance)"
              class="w-full p-3 pl-8 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
          </div>
          <p v-if="form.errors.amount" class="text-red-600 text-sm mt-1">{{ form.errors.amount }}</p>
        </div>

        <!-- Payment Method Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
          <select
            v-model="form.payment_method"
            class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          >
            <option value="">Select a payment method</option>
            <option value="online">Online Transfer</option>
            <option value="card">Credit/Debit Card</option>
            <option value="cash">Cash</option>
            <option value="check">Check</option>
          </select>
          <p v-if="form.errors.payment_method" class="text-red-600 text-sm mt-1">{{ form.errors.payment_method }}</p>
        </div>

        <!-- Reference Number (for online transfers) -->
        <div v-if="form.payment_method === 'online'">
          <label class="block text-sm font-medium text-gray-700 mb-2">Reference Number / Transaction ID</label>
          <input
            v-model="form.reference_number"
            type="text"
            placeholder="e.g., REMIT-12345678"
            class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          />
          <p v-if="form.errors.reference_number" class="text-red-600 text-sm mt-1">{{ form.errors.reference_number }}</p>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes (Optional)</label>
          <textarea
            v-model="form.notes"
            placeholder="Any additional information about this payment..."
            rows="3"
            class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          ></textarea>
        </div>

        <!-- Terms Acceptance -->
        <div class="flex items-start gap-3">
          <input
            v-model="form.terms_accepted"
            type="checkbox"
            id="terms"
            class="mt-1"
          />
          <label for="terms" class="text-sm text-gray-700">
            I confirm that this payment information is accurate and will be subject to verification by the accounting department.
          </label>
        </div>
        <p v-if="form.errors.terms_accepted" class="text-red-600 text-sm">{{ form.errors.terms_accepted }}</p>

        <!-- Submit Button -->
        <div class="flex gap-3 pt-4 border-t">
          <button
            type="button"
            @click="$router.back()"
            class="flex-1 px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-medium transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="flex-1 px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
          >
            {{ form.processing ? 'Submitting...' : 'Submit Payment' }}
          </button>
        </div>
      </form>

      <!-- Info Box -->
      <div class="border-l-4 border-blue-500 bg-blue-50 p-4 rounded">
        <p class="text-sm text-blue-800">
          <strong>Note:</strong> After submission, your payment will be reviewed by the accounting department. You will receive a confirmation email once it has been verified.
        </p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'

interface Props {
  studentName: string
  outstandingBalance: number
}

const props = defineProps<Props>()

const form = useForm({
  amount: 0,
  payment_method: '',
  reference_number: '',
  notes: '',
  terms_accepted: false,
})

const breadcrumbs = [
  { title: 'Dashboard', href: route('student.dashboard') },
  { title: 'Account', href: route('student.account') },
  { title: 'Make Payment' },
]

const outstandingBalance = computed(() => props.outstandingBalance)
const studentName = computed(() => props.studentName)
const paymentMethod = computed(() => {
  const methods: Record<string, string> = {
    online: 'Online Transfer',
    card: 'Credit/Debit Card',
    cash: 'Cash',
    check: 'Check',
  }
  return methods[form.payment_method] || ''
})

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount)
}

const submitPayment = () => {
  form.post(route('account.pay-now'), {
    onSuccess: () => {
      // Success is handled by the backend redirect
    },
  })
}
</script>
