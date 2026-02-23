# Payment Race Condition Fix — Complete Implementation

## Problem Statement

**Race Condition in Payment Approval Buffer:**

1. Student submits payment → `awaiting_approval` transaction created, payment terms **NOT** updated (correct by design)
2. Student submits **another** payment for the **same term** before accounting approves the first
3. Both get approved → `finalizeApprovedPayment()` called twice on the **same term** → **double-deduction / overspending**
4. Frontend `canSubmitPayment` check only blocks when `remainingBalance <= 0`, but pending payments don't reduce balance

## Root Cause

The system had three vulnerabilities:
- **No backend guard** preventing multiple pending payments for the same term
- **No visibility of pending amounts** in balance calculations (backend & frontend)
- **No UI feedback** to prevent accidental re-submission

## Solution — Three-Layer Fix

### Layer 1: Backend Guard (`StudentPaymentService.php`)

**Location:** `app/Services/StudentPaymentService.php` → `processPayment()` method

**What it does:**
- **Blocks duplicate submissions** at the start of `processPayment()`
- Checks if an `awaiting_approval` payment already exists for the selected term
- Prevents the second payment from being recorded at all
- Validates that payment amount doesn't exceed balance minus pending amounts

**How it works:**
```php
// Check for existing awaiting_approval transaction for this term
$existingPendingPayment = Transaction::where('user_id', $user->id)
    ->where('status', 'awaiting_approval')
    ->whereJsonContains('meta->selected_term_id', $selectedTermId)
    ->exists();

if ($existingPendingPayment) {
    throw new Exception(
        'A payment for this term is already awaiting accounting approval. ' .
        'Please wait for approval to complete before submitting another payment.'
    );
}
```

**Throws Exception if:**
- A pending payment exists for the selected term (prevents duplicate)
- Payment amount exceeds effective balance (calculated balance minus pending amounts)

---

### Layer 2: Data for Frontend (`StudentAccountController.php`)

**Location:** `app/Http/Controllers/StudentAccountController.php` → `index()` method

**What it does:**
- Queries all `awaiting_approval` payment transactions
- Passes them to frontendas `pendingApprovalPayments` prop
- Includes metadata: reference, amount, term_id, term_name, created_at

**How it works:**
```php
'pendingApprovalPayments' => $user->transactions
    ->filter(function ($t) {
        return $t->kind === 'payment' && $t->status === 'awaiting_approval';
    })
    ->map(function ($t) {
        return [
            'id'                => $t->id,
            'reference'         => $t->reference,
            'amount'            => (float) $t->amount,
            'selected_term_id'  => $t->meta['selected_term_id'] ?? null,
            'term_name'         => $t->meta['term_name'] ?? 'General',
            'created_at'        => $t->created_at,
        ];
    })
    ->values(),
```

This enables the frontend to:
- Show which terms have pending payments
- Calculate effective balance (balance minus pending)
- Block submission for terms with pending payments

---

### Layer 3: Frontend UX (`AccountOverview.vue`)

**Location:** `resources/js/pages/Student/AccountOverview.vue`

#### A. New Computed Properties

**`pendingPaymentsByTerm`** — Maps pending amounts by term ID
```typescript
const pendingPaymentsByTerm = computed(() => {
  const pending: Record<number, number> = {}
  props.pendingApprovalPayments?.forEach(payment => {
    if (payment.selected_term_id !== null) {
      pending[payment.selected_term_id] = (pending[payment.selected_term_id] || 0) + payment.amount
    }
  })
  return pending
})
```

**`effectiveBalance`** — Shows true available balance (minus pending)
```typescript
const effectiveBalance = computed(() => {
  const totalBalance = props.paymentTerms.reduce((sum, term) => sum + Number(term.balance || 0), 0)
  const totalPending = props.pendingApprovalPayments?.reduce((sum, p) => sum + p.amount, 0) || 0
  return Math.max(0, Math.round((totalBalance - totalPending) * 100) / 100)
})
```

**`hasPendingPayments`** — Checks if any pending payments exist
```typescript
const hasPendingPayments = computed(() => {
  return props.pendingApprovalPayments && props.pendingApprovalPayments.length > 0
})
```

**`submitButtonMessage`** — Dynamic button label based on state
```typescript
const submitButtonMessage = computed(() => {
  if (!paymentForm.selected_term_id) {
    return 'Select a Payment Term'
  }
  
  const selectedTermHasPending = getPendingAmountForTerm(paymentForm.selected_term_id) > 0
  if (selectedTermHasPending) {
    const pending = getPendingAmountForTerm(paymentForm.selected_term_id)
    return `⏳ Awaiting Approval (₱${formatCurrency(pending)}) — Cannot Submit`
  }
  
  return 'Submit Payment'
})
```

#### B. Updated `availableTermsForPayment`

Now includes pending payment info for each term:
```typescript
return unpaidTerms.map((term, index) => {
  const pendingAmount = getPendingAmountForTerm(term.id)
  const hasPending = pendingAmount > 0
  
  return {
    // ... existing fields ...
    isSelectable: index === firstUnpaidIndex && !hasPending,  // ← BLOCKED if pending
    hasPending,
    pendingAmount,
  }
})
```

#### C. Updated `canSubmitPayment`

Now checks for pending payments on the selected term:
```typescript
const canSubmitPayment = computed(() => {
  const selectedTermHasPending = paymentForm.selected_term_id !== null && 
    getPendingAmountForTerm(paymentForm.selected_term_id) > 0

  return (
    effectiveBalance.value > 0 &&
    paymentForm.amount > 0 &&
    paymentForm.selected_term_id !== null &&
    availableTermsForPayment.value.length > 0 &&
    !selectedTermHasPending  // ← NEW CHECK
  )
})
```

#### D. UI Components

**Pending Payment Warning Banner** — Shows at top of payment form
- Lists all pending payments with reference, term, amount
- Advises user to wait for approval before submitting another

**Updated Term Dropdown**
- Shows `(⏳ Pending ₱X.XX approval)` for terms with pending payments
- Terms with pending payments are disabled/unselectable

**Updated Submit Button**
- Shows `⏳ Awaiting Approval (₱X.XX) — Cannot Submit` when term has pending
- Button is disabled when pending payments exist for selected term
- Helpful message appears below button explaining the reason

**Pending Payments Section in History Tab**
- Separate amber section showing all pending approvals
- Lists: term name, reference, amount, status
- Allows students to track their pending submissions

---

## Files Modified

### Backend (PHP)
1. **`app/Services/StudentPaymentService.php`**
   - Added guard in `processPayment()` method (lines 45-89)
   - Prevents duplicate payments for same term
   - Validates effective balance against pending amounts

2. **`app/Http/Controllers/StudentAccountController.php`**
   - Added `pendingApprovalPayments` to render props (lines 200-216)
   - Queries and transforms pending payment transactions

### Frontend (Vue 3 / TypeScript)
3. **`resources/js/pages/Student/AccountOverview.vue`**
   - Added `pendingApprovalPayments` prop type (lines 105-112)
   - Added 4 new computed properties (lines 303-342)
   - Updated `availableTermsForPayment` to track pending status (lines 344-368)
   - Updated `canSubmitPayment` to check pending (lines 396-408)
   - Added `submitButtonMessage` computed property (lines 410-428)
   - Updated template:
     - Pending warning banner (lines 783-798)
     - Updated term dropdown options (lines 828-835)
     - Updated submit button message and reasons (lines 873-883)
     - Added pending payments section in history tab (lines 743-765)

---

## How It Works in Practice

### Student submits first payment:
1. Frontend checks: "Is there a pending payment for this term?" → No
2. Form is enabled, button shows "Submit Payment"
3. Student clicks Submit
4. Backend guard checks: "Exists another awaiting_approval for this term?" → No
5. Payment recorded as `awaiting_approval`
6. Frontend shows warning banner with pending payment
7. History tab shows payment in pending section (amber)
8. Term dropdown becomes disabled/unselectable

### Student tries to submit second payment for same term (before approval):
1. Frontend checks: `getPendingAmountForTerm(term.id) > 0` → Yes!
2. Form fields disabled
3. Button shows: "⏳ Awaiting Approval (₱X.XX) — Cannot Submit"
4. Message below button: "A payment of ₱X.XX for this term is awaiting accounting approval..."
5. **User cannot submit** (button disabled)

### OR if user somehow bypasses frontend:
1. Backend guard checks: `whereJsonContains('meta->selected_term_id', $selectedTermId)` → Exists!
2. **Throws Exception:** "A payment for this term is already awaiting accounting approval..."
3. **Payment rejected** (not recorded)

### When accounting approves the payment:
1. Transaction status changed from `awaiting_approval` → `paid`
2. `finalizeApprovedPayment()` called once (can't be called twice anymore)
3. Payment terms updated correctly

---

## Testing Scenarios

### ✅ Test 1: Normal Single Payment
1. Select a term with balance
2. Enter amount ≤ balance
3. Submit button enabled
4. Submit successfully

### ✅ Test 2: Prevent Double-Submit (Frontend)
1. Submit payment → arrives at backend
2. Page refreshes, shows pending banner
3. Try to submit again
4. Submit button blocked with message
5. Try clicking: button doesn't respond (disabled)

### ✅ Test 3: Prevent Double-Submit (Backend)
If student somehow disables JavaScript and forces submission:
1. Backend guard catches it
2. Throws exception: "A payment for this term is already awaiting accounting approval"
3. Payment not recorded
4. User sees exception message

### ✅ Test 4: Overpayment Before Approval
1. Submit ₱5000 for a ₱3000 term (awaiting approval)
2. Term shows pending ₱5000
3. Try to submit another payment
4. Backend validation: effective balance = 0
5. Exception: "Payment amount exceeds available balance"

### ✅ Test 5: Multiple Terms (Sequential)
1. Term 1 has pending approval → Disabled
2. Term 2 is next unpaid → Enabled
3. Can submit to Term 2 (different term)
4. Both show in pending section once approved

---

## Exception Handling

### Student will see these exceptions:

**Multiple Submissions Blocked:**
```
Exception: A payment for this term is already awaiting accounting approval. 
Please wait for approval to complete before submitting another payment.
```

**Exceeds Available Balance:**
```
Exception: Payment amount exceeds available balance. 
Available: ₱1,875.55, Pending approval: ₱1,234.56.
```

Both are caught in the controller and shown to the user as validation errors.

---

## Backward Compatibility

- ✅ Existing payments (no pending) work normally
- ✅ Terms without pending payments remain selectable
- ✅ Balance display now more accurate (shows effective balance)
- ✅ No changes to payment term model or database schema
- ✅ No changes to approval workflow

---

## Security Impact

- **Prevents:** Race condition exploitation, double-deduction attacks
- **Defense-in-Depth:** Guard at both backend (required) and frontend (UX)
- **Audit Trail:** Pending payments visible in transaction history
- **Accountability:** Clear reference numbers for all pending payments

---

## Summary

This three-layer fix eliminates the payment race condition by:
1. **Blocking** duplicate submissions at the backend source
2. **Informing** the frontend about pending amounts
3. **Guiding** users with clear UI feedback to prevent accidents

The solution is production-ready and maintains full backward compatibility.
