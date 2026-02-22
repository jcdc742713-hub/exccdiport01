<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import { useDataFormatting } from '@/composables/useDataFormatting'
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

const {
  formatCurrency,
  formatDate,
  getTransactionStatusConfig,
  formatTransactionType,
} = useDataFormatting()

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

type PaymentTerm = {
  id: number
  term_name: string
  term_order: number
  percentage: number
  amount: number
  balance: number
  due_date: string
  status: string
  remarks: string | null
  paid_date: string | null
}

type Assessment = {
  id: number
  assessment_number: string
  total_assessment: number
  status: string
  created_at: string
}

type PaymentReminder = {
  id: number
  type: string
  message: string
  outstanding_balance: number
  status: string
  read_at: string | null
  sent_at: string
  trigger_reason: string
}

const props = defineProps<{
  account: Account
  notifications: Notification[]
  recentTransactions: RecentTransaction[]
  paymentTerms?: PaymentTerm[]
  latestAssessment?: Assessment | null
  paymentReminders?: PaymentReminder[]
  unreadReminderCount?: number
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

// ============================================================================
// DATA NORMALIZATION & VALIDATION LAYER
// ============================================================================

/**
 * Normalize and validate financial stats from props
 * Ensures all values are valid, non-negative numbers
 * Uses payment terms for remaining balance (most accurate)
 */
const normalizedStats = computed(() => {
  const safeNumber = (value: any): number => {
    // Handle null, undefined
    if (value === null || value === undefined) return 0
    
    // Convert to number
    const num = Number(value)
    
    // Handle NaN, Infinity
    if (!isFinite(num)) return 0
    
    // Prevent negative values in financial context
    return Math.max(0, num)
  }

  // Total fees: use assessment total if available (matches AccountOverview)
  const totalFees = props.latestAssessment 
    ? safeNumber(props.latestAssessment.total_assessment)
    : safeNumber(props.stats?.total_fees)

  // Calculate remaining balance from payment terms if available (most accurate)
  // This matches the AccountOverview calculation logic
  let remainingBalance = safeNumber(props.stats?.remaining_balance)
  
  if (props.paymentTerms && props.paymentTerms.length > 0) {
    // Sum all balances from payment terms - they are the source of truth
    remainingBalance = safeNumber(
      props.paymentTerms.reduce((sum, term) => sum + (term.balance || 0), 0)
    )
  }

  return {
    total_fees: totalFees,
    total_paid: safeNumber(props.stats?.total_paid),
    remaining_balance: remainingBalance,
    pending_charges_count: Math.floor(Math.max(0, safeNumber(props.stats?.pending_charges_count))),
  }
})

/**
 * Calculate payment percentage with safe division
 * Result is capped at 100% and protected from division errors
 */
const getPaymentPercentage = computed(() => {
  // Guard against division by zero
  if (normalizedStats.value.total_fees === 0) return 0
  
  const percentage = (normalizedStats.value.total_paid / normalizedStats.value.total_fees) * 100
  
  // Round to nearest integer and cap at 100%
  return Math.min(100, Math.round(percentage))
})

/**
 * Validate financial consistency
 * Checks if data makes mathematical sense
 */
const financialDataIsConsistent = computed(() => {
  const { total_fees, total_paid, remaining_balance } = normalizedStats.value
  
  // If we have payment terms, validate against their sum
  if (props.paymentTerms && props.paymentTerms.length > 0) {
    const paymentTermsBalance = props.paymentTerms.reduce((sum, term) => sum + (term.balance || 0), 0)
    const tolerance = 0.01 // Allow 1 cent difference for rounding
    return Math.abs(remaining_balance - paymentTermsBalance) < tolerance
  }
  
  // Fallback: Check if balance equals fees minus paid
  const expectedRemaining = Math.max(0, total_fees - total_paid)
  const tolerance = 0.01 // Allow 1 cent difference for rounding
  
  return Math.abs(remaining_balance - expectedRemaining) < tolerance
})

/**
 * Determine financial state for messaging and styling
 */
const paymentState = computed<'fully_paid' | 'in_progress' | 'nearly_due' | 'attention_needed'>(() => {
  const balance = normalizedStats.value.remaining_balance
  const percentage = getPaymentPercentage.value
  
  if (balance === 0) return 'fully_paid'
  if (percentage >= 75) return 'nearly_due'
  if (percentage >= 50) return 'in_progress'
  return 'attention_needed'
})

/**
 * Pending charges context
 * Provides clear, accurate information about pending items
 */
const pendingChargesInfo = computed(() => {
  const count = normalizedStats.value.pending_charges_count
  
  return {
    count,
    label: count === 0 
      ? 'No Pending Charges'
      : count === 1
      ? '1 Pending Charge'
      : `${count} Pending Charges`,
    hasWarning: count > 0,
    description: count === 0
      ? 'All charges are processed'
      : 'Charges awaiting processing',
  }
})

/**
 * Get unpaid payment terms (terms that still have a balance)
 * Returns terms ordered by term_order for display
 */
const unpaidTerms = computed(() => {
  if (!props.paymentTerms || props.paymentTerms.length === 0) {
    return []
  }
  
  return props.paymentTerms
    .filter(term => term.balance > 0)
    .sort((a, b) => a.term_order - b.term_order)
})

/**
 * Determine due date color based on proximity to due date
 * Red if: 1 week before due date OR after 1 day past due date
 * Amber if: due date is approaching (2-7 days)
 * Green otherwise (not yet due or recently paid)
 */
const getDueDateColor = (dueDate: string): 'red' | 'amber' | 'green' => {
  const now = new Date()
  const due = new Date(dueDate)
  
  // Calculate days difference
  const diffTime = due.getTime() - now.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  // More than 1 day past due = red
  if (diffDays < -1) return 'red'
  
  // 1 week before due date or after 1 day past due = red
  if (diffDays <= 7 && diffDays >= -1) return 'red'
  
  // 2 weeks before due date = amber
  if (diffDays <= 14) return 'amber'
  
  // Otherwise green (not yet due)
  return 'green'
}

/**
 * Get the first unpaid term (next payment due)
 * This is the term that requires immediate attention
 */
const nextPaymentDue = computed(() => {
  if (unpaidTerms.value.length === 0) {
    return null
  }
  
  const term = unpaidTerms.value[0]
  const dueColor = getDueDateColor(term.due_date)
  const daysUntilDue = Math.ceil(
    (new Date(term.due_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24)
  )
  
  return {
    ...term,
    dueColor,
    daysUntilDue,
    formattedDueDate: formatDate(term.due_date),
    isDueOrOverdue: daysUntilDue <= 7,
  }
})

/**
 * Get remaining unpaid terms (for pending charges display)
 */
const remainingUnpaidTerms = computed(() => {
  if (unpaidTerms.value.length <= 1) {
    return []
  }
  
  return unpaidTerms.value.slice(1).map(term => ({
    ...term,
    formattedDueDate: formatDate(term.due_date),
  }))
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

/**
 * Track whether to show all notifications or just the first 3
 */
const showAllNotifications = ref(false)

/**
 * Show only the first 3 notifications, or all if showAllNotifications is true
 */
const visibleNotifications = computed(() => {
  return showAllNotifications.value
    ? activeNotifications.value
    : activeNotifications.value.slice(0, 3)
})

/**
 * Check if there are more notifications than the 3 shown by default
 */
const hasMoreNotifications = computed(() => {
  return activeNotifications.value.length > 3
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
          <!-- Total Assessment Fee Card -->
          <div class="bg-white rounded-lg shadow-md p-6 flex items-center gap-4 border-l-4 border-blue-300">
            <div class="p-3 bg-blue-100 rounded-lg">
              <FileText :size="24" class="text-blue-600" />
            </div>
            <div>
              <p class="text-sm text-gray-600">Total Assessment Fee</p>
              <p class="text-2xl font-bold text-blue-700">
                {{ formatCurrency(normalizedStats.total_fees) }}
              </p>
            </div>
          </div>

          <!-- Total Paid Card -->
          <div class="bg-white rounded-lg shadow-md p-6 flex items-center gap-4 border-l-4 border-green-300">
            <div class="p-3 bg-green-100 rounded-lg">
              <CheckCircle :size="24" class="text-green-600" />
            </div>
            <div>
              <p class="text-sm text-gray-600">Total Paid</p>
              <p class="text-2xl font-bold text-green-600">
                {{ formatCurrency(normalizedStats.total_paid) }}
              </p>
            </div>
          </div>

          <!-- Remaining Balance Card -->
          <div :class="[
            'bg-white rounded-lg shadow-md p-6 flex items-center gap-4 border-l-4',
            normalizedStats.remaining_balance > 0 ? 'border-red-300' : 'border-green-300'
          ]">
            <div :class="[
              'p-3 rounded-lg',
              normalizedStats.remaining_balance > 0 ? 'bg-red-100' : 'bg-green-100'
            ]">
              <Wallet :size="24" :class="[
                normalizedStats.remaining_balance > 0 ? 'text-red-600' : 'text-green-600'
              ]" />
            </div>
            <div>
              <p class="text-sm text-gray-600">Remaining Balance</p>
              <p :class="[
                'text-2xl font-bold',
                normalizedStats.remaining_balance > 0 ? 'text-red-600' : 'text-green-600'
              ]">
                {{ formatCurrency(normalizedStats.remaining_balance) }}
              </p>
            </div>
          </div>

          <!-- Pending Charges Card -->
          <div :class="[
            'bg-white rounded-lg shadow-md p-6 flex items-center gap-4 border-l-4',
            pendingChargesInfo.hasWarning ? 'border-yellow-300' : 'border-gray-300'
          ]">
            <div :class="[
              'p-3 rounded-lg',
              pendingChargesInfo.hasWarning ? 'bg-yellow-100' : 'bg-gray-100'
            ]">
              <Clock :size="24" :class="[
                pendingChargesInfo.hasWarning ? 'text-yellow-600' : 'text-gray-500'
              ]" />
            </div>
            <div>
              <p class="text-sm text-gray-600">Pending Charges</p>
              <p :class="[
                'text-2xl font-bold',
                pendingChargesInfo.hasWarning ? 'text-yellow-600' : 'text-gray-700'
              ]">
                {{ pendingChargesInfo.count }}
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
          <!-- Payment Reminders History -->
          <div v-if="props.paymentReminders && props.paymentReminders.length > 0" class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <h2 class="text-xl font-semibold">Payment Reminders</h2>
                <span v-if="props.unreadReminderCount && props.unreadReminderCount > 0" class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                  {{ props.unreadReminderCount }} new
                </span>
              </div>
            </div>

            <div class="space-y-3">
              <div
                v-for="reminder in props.paymentReminders"
                :key="reminder.id"
                :class="[
                  'p-4 rounded-lg border-l-4',
                  reminder.type === 'overdue' || reminder.type === 'approaching_due'
                    ? 'bg-red-50 border-red-400'
                    : reminder.type === 'partial_payment'
                    ? 'bg-yellow-50 border-yellow-400'
                    : 'bg-blue-50 border-blue-400'
                ]"
              >
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <h4 :class="[
                      'font-semibold text-sm',
                      reminder.type === 'overdue' || reminder.type === 'approaching_due'
                        ? 'text-red-900'
                        : reminder.type === 'partial_payment'
                        ? 'text-yellow-900'
                        : 'text-blue-900'
                    ]">
                      {{ reminder.message }}
                    </h4>
                    <p class="text-xs text-gray-600 mt-1">
                      {{ formatDate(reminder.sent_at) }}
                    </p>
                  </div>
                  <span :class="[
                    'text-xs px-2 py-1 rounded font-medium whitespace-nowrap ml-2',
                    reminder.status === 'read'
                      ? 'bg-gray-100 text-gray-700'
                      : 'bg-red-100 text-red-700'
                  ]">
                    {{ reminder.status === 'read' ? 'Read' : 'Unread' }}
                  </span>
                </div>
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
                  <p class="font-medium">{{ formatTransactionType(transaction.type) }}</p>
                  <p class="text-sm text-gray-600">{{ transaction.reference || 'N/A' }}</p>
                  <p class="text-xs text-gray-500">
                    {{ transaction.created_at ? formatDate(transaction.created_at) : '-' }}
                  </p>
                </div>

                <div class="text-right">
                  <p class="font-semibold">
                    {{ formatCurrency(transaction.amount) }}
                  </p>
                  <span
                    class="text-xs px-2 py-1 rounded font-medium"
                    :class="{
                      ...getTransactionStatusConfig(transaction.status)
                    }"
                  >
                    {{ getTransactionStatusConfig(transaction.status).label }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN - PAYMENT STATUS COMMUNICATION -->
        <div class="space-y-6">
          <!-- Payment Due - Shows Next Payment Term -->
          <div v-if="nextPaymentDue" :class="[
            'border-2 rounded-lg p-6 shadow-md',
            nextPaymentDue.dueColor === 'red' 
              ? 'bg-gradient-to-br from-red-50 to-red-100 border-red-300'
              : nextPaymentDue.dueColor === 'amber'
              ? 'bg-gradient-to-br from-amber-50 to-amber-100 border-amber-300'
              : 'bg-gradient-to-br from-green-50 to-green-100 border-green-300'
          ]">
            <!-- Header -->
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 :class="[
                  'font-semibold text-lg',
                  nextPaymentDue.dueColor === 'red'
                    ? 'text-red-900'
                    : nextPaymentDue.dueColor === 'amber'
                    ? 'text-amber-900'
                    : 'text-green-900'
                ]">
                  {{ nextPaymentDue.term_name }}
                </h3>
                <p :class="[
                  'text-xs mt-1',
                  nextPaymentDue.dueColor === 'red'
                    ? 'text-red-700'
                    : nextPaymentDue.dueColor === 'amber'
                    ? 'text-amber-700'
                    : 'text-green-700'
                ]">
                  {{ nextPaymentDue.isDueOrOverdue ? 'Payment due soon' : 'Upcoming payment' }}
                </p>
              </div>
              <div :class="[
                'p-2 rounded-lg',
                nextPaymentDue.dueColor === 'red'
                  ? 'bg-red-200'
                  : nextPaymentDue.dueColor === 'amber'
                  ? 'bg-amber-200'
                  : 'bg-green-200'
              ]">
                <AlertCircle v-if="nextPaymentDue.dueColor === 'red'" :size="20" :class="[
                  nextPaymentDue.dueColor === 'red' ? 'text-red-700' : ''
                ]" />
                <Clock v-else-if="nextPaymentDue.dueColor === 'amber'" :size="20" class="text-amber-700" />
                <CheckCircle v-else :size="20" class="text-green-700" />
              </div>
            </div>

            <!-- Term Details -->
            <div :class="[
              'rounded-lg p-4 mb-4 border',
              nextPaymentDue.dueColor === 'red'
                ? 'bg-white bg-opacity-60 border-red-200'
                : nextPaymentDue.dueColor === 'amber'
                ? 'bg-white bg-opacity-60 border-amber-200'
                : 'bg-white bg-opacity-60 border-green-200'
            ]">
              <div class="space-y-3">
                <!-- Amount Due -->
                <div>
                  <p :class="[
                    'text-xs font-medium mb-1',
                    nextPaymentDue.dueColor === 'red'
                      ? 'text-red-700'
                      : nextPaymentDue.dueColor === 'amber'
                      ? 'text-amber-700'
                      : 'text-green-700'
                  ]">
                    Amount Due
                  </p>
                  <p :class="[
                    'text-2xl font-bold',
                    nextPaymentDue.dueColor === 'red'
                      ? 'text-red-700'
                      : nextPaymentDue.dueColor === 'amber'
                      ? 'text-amber-700'
                      : 'text-green-700'
                  ]">
                    {{ formatCurrency(nextPaymentDue.balance) }}
                  </p>
                </div>

                <!-- Due Date with Color Coding -->
                <div class="pt-2 border-t border-gray-300">
                  <p class="text-xs text-gray-600 mb-1">Due Date</p>
                  <div class="flex items-center justify-between">
                    <p :class="[
                      'font-semibold',
                      nextPaymentDue.dueColor === 'red'
                        ? 'text-red-700'
                        : nextPaymentDue.dueColor === 'amber'
                        ? 'text-amber-700'
                        : 'text-gray-700'
                    ]">
                      {{ nextPaymentDue.formattedDueDate }}
                    </p>
                    <span v-if="nextPaymentDue.daysUntilDue >= 0" :class="[
                      'text-xs px-2 py-1 rounded font-medium',
                      nextPaymentDue.dueColor === 'red'
                        ? 'bg-red-100 text-red-700'
                        : nextPaymentDue.dueColor === 'amber'
                        ? 'bg-amber-100 text-amber-700'
                        : 'bg-green-100 text-green-700'
                    ]">
                      {{ nextPaymentDue.daysUntilDue }} day{{ nextPaymentDue.daysUntilDue !== 1 ? 's' : '' }} left
                    </span>
                    <span v-else class="text-xs px-2 py-1 rounded font-medium bg-red-100 text-red-700">
                      {{ Math.abs(nextPaymentDue.daysUntilDue) }} day{{ Math.abs(nextPaymentDue.daysUntilDue) !== 1 ? 's' : '' }} overdue
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
              <Link
                :href="route('student.account')"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-center text-sm transition"
              >
                View Details
              </Link>
              <Link
                :href="route('student.account', { tab: 'payment', term_id: nextPaymentDue.id })"
                class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-center text-sm transition"
              >
                Pay Now
              </Link>
            </div>
          </div>

          <!-- Success State - All Paid -->
          <div v-if="normalizedStats.remaining_balance === 0" class="bg-gradient-to-br from-green-50 to-green-100 border-2 border-green-300 rounded-lg p-6 shadow-md">
            <!-- Header -->
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 class="font-semibold text-green-900 text-lg">Account in Good Standing</h3>
                <p class="text-xs text-green-700 mt-1">All payments are current</p>
              </div>
              <div class="p-2 bg-green-200 rounded-lg">
                <CheckCircle :size="20" class="text-green-700" />
              </div>
            </div>

            <!-- Status Message -->
            <div class="bg-white bg-opacity-60 rounded-lg p-4 mb-4 border border-green-200">
              <p class="text-sm text-green-800">
                Your account balance is fully paid. No payment action is required at this time.
              </p>
            </div>

            <!-- Guidance -->
            <div class="text-xs text-green-700">
              <p class="mb-2">
                <span class="font-semibold">📌 Reminder:</span> Check your dashboard regularly for any new assessment notices or payment terms.
              </p>
              <p>
                <span class="font-semibold">📧 Questions?</span> Contact the Office of the Registrar if you need to verify your account status.
              </p>
            </div>
          </div>

          <!-- Data Integrity Note (development only - can be removed) -->
          <div v-if="!financialDataIsConsistent" class="bg-yellow-50 border border-yellow-400 rounded-lg p-4">
            <p class="text-xs text-yellow-800">
              <span class="font-semibold">⚠️ Note:</span> There is a discrepancy in your financial data. Please contact support if this persists.
            </p>
          </div>

          <!-- Important Updates / Notifications -->
          <div v-if="activeNotifications.length">
            <div class="flex items-center gap-2 mb-4">
              <Bell class="w-6 h-6 text-blue-600" />
              <h2 class="text-xl font-bold text-gray-900">Important Updates</h2>
            </div>

            <div class="space-y-4">
              <div
                v-for="notification in visibleNotifications"
                :key="notification.id"
                class="bg-white rounded-lg shadow-md border-l-4 border-blue-500 p-5 hover:shadow-lg hover:bg-blue-50 transition-all"
              >
                <div class="flex items-start justify-between mb-3">
                  <h3 class="font-bold text-base text-gray-900 flex-1 pr-2">{{ notification.title }}</h3>
                  <div class="flex-shrink-0">
                    <div class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 whitespace-nowrap">
                      ✓ Active
                    </div>
                  </div>
                </div>

                <p class="text-gray-700 text-sm leading-relaxed mb-3">{{ notification.message }}</p>

                <div class="pt-3 border-t border-gray-200 text-xs text-gray-600 space-y-1">
                  <p v-if="notification.start_date">📅 <strong>From:</strong> {{ formatDate(notification.start_date) }}</p>
                  <p v-if="notification.end_date">📅 <strong>Until:</strong> {{ formatDate(notification.end_date) }}</p>
                </div>
              </div>
            </div>

            <!-- View More / Show Less Button -->
            <div v-if="hasMoreNotifications" class="mt-4">
              <button
                @click="showAllNotifications = !showAllNotifications"
                class="w-full text-center py-2 px-4 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
              >
                {{ showAllNotifications 
                  ? 'Show Less' 
                  : `View More Updates (${activeNotifications.length - 3} more)` }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>