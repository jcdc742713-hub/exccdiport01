<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import {
  Wallet,
  Calendar,
  AlertCircle,
  CheckCircle,
  TrendingUp,
  Clock,
  FileText,
  CreditCard,
  Bell,
} from 'lucide-vue-next'

type Notification = {
  id: number
  title: string
  message: string
  start_date: string | null
  end_date: string | null
  target_role: string
}

type Account = {
  balance: number
}

type RecentTransaction = {
  id: number
  reference: string
  type: string
  amount: number
  status: string
  created_at: string
}

const props = defineProps<{
  account: Account
  notifications: Notification[]
  recentTransactions: RecentTransaction[]
  stats: {
    total_fees: number
    total_paid: number
    remaining_balance: number
    pending_charges_count: number
  }
}>()

const breadcrumbs = [
  { title: 'Dashboard', href: route('dashboard') },
  { title: 'Student Dashboard' },
]

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
  }).format(amount)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

const getPaymentPercentage = computed(() => {
  if (props.stats.total_fees === 0) return 0
  return Math.round((props.stats.total_paid / props.stats.total_fees) * 100)
})

const activeNotifications = computed(() => {
  const now = new Date()
  return props.notifications.filter(n => {
    if (!n.start_date) return true
    const startDate = new Date(n.start_date)
    const endDate = n.end_date ? new Date(n.end_date) : null
    return startDate <= now && (!endDate || endDate >= now)
  })
})
</script>

<template>
  <AppLayout>
    <Head title="Student Dashboard" />

    <div class="w-full p-6 space-y-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Welcome Header -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 text-white">
        <h1 class="text-3xl font-bold mb-2">Welcome Back, Student!</h1>
        <p class="text-blue-100">
          Here's your financial overview and important updates
        </p>
      </div>

      <!-- QUICK STATS + QUICK ACTIONS -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Stats (2x2) -->
        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div class="bg-white rounded-lg shadow-md p-6 flex items-center gap-4">
            <div class="p-3 bg-blue-100 rounded-lg">
              <FileText :size="24" class="text-blue-600" />
            </div>
            <div>
              <p class="text-sm text-gray-600">Total Fees</p>
              <p class="text-2xl font-bold">
                {{ formatCurrency(stats.total_fees) }}
              </p>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-md p-6 flex items-center gap-4">
            <div class="p-3 bg-green-100 rounded-lg">
              <CheckCircle :size="24" class="text-green-600" />
            </div>
            <div>
              <p class="text-sm text-gray-600">Total Paid</p>
              <p class="text-2xl font-bold text-green-600">
                {{ formatCurrency(stats.total_paid) }}
              </p>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-md p-6 flex items-center gap-4">
            <div class="p-3 bg-red-100 rounded-lg">
              <Wallet :size="24" class="text-red-600" />
            </div>
            <div>
              <p class="text-sm text-gray-600">Remaining Balance</p>
              <p class="text-2xl font-bold text-red-600">
                {{ formatCurrency(stats.remaining_balance) }}
              </p>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-md p-6 flex items-center gap-4">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <Clock :size="24" class="text-yellow-600" />
            </div>
            <div>
              <p class="text-sm text-gray-600">Pending Charges</p>
              <p class="text-2xl font-bold text-yellow-600">
                {{ stats.pending_charges_count }}
              </p>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>

          <div class="space-y-3">
            <Link
              :href="route('student.account')"
              class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg hover:bg-blue-100"
            >
              <Wallet :size="20" class="text-blue-600" />
              <span class="font-medium">View Account</span>
            </Link>

            <Link
              :href="route('student.account', { tab: 'payment' })"
              class="flex items-center gap-3 p-3 bg-green-50 rounded-lg hover:bg-green-100"
            >
              <CreditCard :size="20" class="text-green-600" />
              <span class="font-medium">Make Payment</span>
            </Link>

            <Link
              :href="route('transactions.index')"
              class="flex items-center gap-3 p-3 bg-purple-50 rounded-lg hover:bg-purple-100"
            >
              <FileText :size="20" class="text-purple-600" />
              <span class="font-medium">View History</span>
            </Link>
          </div>
        </div>
      </div>

      <!-- MAIN CONTENT -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT COLUMN -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Notifications -->
          <div v-if="activeNotifications.length" class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Important Announcements</h2>

            <div class="space-y-4">
              <div
                v-for="notification in activeNotifications"
                :key="notification.id"
                class="border-l-4 border-blue-500 bg-blue-50 p-4"
              >
                <h3 class="font-semibold">{{ notification.title }}</h3>
                <p class="text-sm mt-1">{{ notification.message }}</p>
              </div>
            </div>
          </div>

          <!-- ✅ Recent Transactions (RETAINED) -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-semibold">Recent Transactions</h2>
              <Link
                :href="route('transactions.index')"
                class="text-sm text-blue-600 hover:underline"
              >
                View All →
              </Link>
            </div>

            <p v-if="!recentTransactions.length" class="text-gray-500 text-center py-4">
              No recent transactions
            </p>

            <div v-else class="space-y-3">
              <div
                v-for="transaction in recentTransactions"
                :key="transaction.id"
                class="flex justify-between items-center p-3 hover:bg-gray-50 rounded"
              >
                <div>
                  <p class="font-medium">{{ transaction.type }}</p>
                  <p class="text-sm text-gray-600">{{ transaction.reference }}</p>
                  <p class="text-xs text-gray-500">
                    {{ formatDate(transaction.created_at) }}
                  </p>
                </div>

                <div class="text-right">
                  <p class="font-semibold">
                    {{ formatCurrency(transaction.amount) }}
                  </p>
                  <span
                    class="text-xs px-2 py-1 rounded"
                    :class="transaction.status === 'paid'
                      ? 'bg-green-100 text-green-800'
                      : 'bg-yellow-100 text-yellow-800'"
                  >
                    {{ transaction.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="space-y-6">
          <div
            v-if="stats.remaining_balance > 0"
            class="bg-red-50 border border-red-200 rounded-lg p-6"
          >
            <h3 class="font-semibold text-red-700 mb-2">Payment Reminder</h3>
            <p class="text-sm">
              Outstanding balance:
              <strong>{{ formatCurrency(stats.remaining_balance) }}</strong>
            </p>
          </div>

          <div
            v-else
            class="bg-green-50 border border-green-200 rounded-lg p-6"
          >
            <h3 class="font-semibold text-green-700 mb-2">All Paid 🎉</h3>
            <p class="text-sm">You have no outstanding balance.</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
