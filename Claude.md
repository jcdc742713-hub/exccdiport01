# CLAUDE.md — CCDI Account Portal

> Project instructions for Claude Code (VS Code Agent). Read this before making any changes.

---

## Project Overview

**CCDI Account Portal** — A school financial management system for CCDI (a Philippine educational institution). Handles student enrollment, fee assessment, payment tracking, and accounting workflows across three user roles.

**Stack:** Laravel 12 + Vue 3 + Inertia.js + TypeScript + Tailwind CSS v4 + SQLite  
**Pattern:** Monolithic SPA via Inertia.js (no REST API for frontend — data flows through controller props)

---

## Local Development

```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed   # if seeders exist

# Start all services (server + queue + vite)
composer run dev
```

**Runs three processes concurrently:**
- `php artisan serve` → http://localhost:8000
- `php artisan queue:listen` → background jobs
- `npm run dev` → Vite HMR

**Build for production:**
```bash
npm run build
php artisan optimize
```

**Code formatting:**
```bash
npm run format          # Prettier (Vue/TS/CSS)
npm run lint            # ESLint --fix
./vendor/bin/pint       # Laravel Pint (PHP)
```

---

## Architecture

### Role System

Three roles enforced by `RoleMiddleware` (`app/Http/Middleware/RoleMiddleware.php`):

| Role | Enum Value | Dashboard Route | Access |
|------|-----------|-----------------|--------|
| `admin` | `UserRoleEnum::ADMIN` | `admin.dashboard` | Full system access |
| `accounting` | `UserRoleEnum::ACCOUNTING` | `accounting.dashboard` | Financial ops, read-only student archive |
| `student` | `UserRoleEnum::STUDENT` | `student.dashboard` | Own account only |

Role is stored as a cast enum on `users.role` using `App\Enums\UserRoleEnum`.  
Admin sub-types (`super`, `manager`, `operator`) are on `users.admin_type` — separate from role.

Route groups use: `->middleware(['auth', 'verified', 'role:admin'])` etc.

### Page Structure

```
resources/js/pages/
├── Admin/              # Admin-only pages
│   ├── Dashboard.vue
│   ├── Notifications/  # Index, Show, Form (Create+Edit)
│   └── Users/          # Index, Create, Edit, Show
├── Student/            # Student-facing pages (their own data)
│   ├── Dashboard.vue
│   └── AccountOverview.vue
├── Students/           # Admin-only student archive
│   ├── Index.vue
│   ├── Create.vue
│   ├── Edit.vue
│   └── StudentProfile.vue  # ← show() renders THIS (not Show.vue)
├── StudentFees/        # Admin+Accounting fee management per student
├── Fees/               # Fee catalog (admin+accounting)
├── Accounting/         # Accounting dashboard + workflows
├── Transactions/       # Transaction history
└── Workflows/          # Enrollment approval workflows
```

> ⚠️ `Students/Show.vue` and `Students/View.vue` are **dead files** — no controller renders them. `StudentController::show()` renders `Students/StudentProfile`.

### Inertia Data Flow

Controllers pass props directly to Vue pages via `Inertia::render()`. There is no Vuex/Pinia store. Shared data (auth, app name, CSRF) is injected globally via `HandleInertiaRequests::share()`.

```php
// Controller → Page
return Inertia::render('Admin/Dashboard', [
    'stats' => $stats,
]);
```

```vue
<!-- Page receives as props -->
const props = defineProps<{ stats: Stats }>()
```

Auth is always available in every page as `$page.props.auth.user`.

---

## Frontend Conventions

### Breadcrumbs — REQUIRED PATTERN

Every admin/staff page must follow **exactly** the `Fees/Index.vue` pattern:

```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'

const breadcrumbs = [
  { title: 'Dashboard', href: route('admin.dashboard') },
  { title: 'Page Name', href: route('resource.index') },
]
</script>

<template>
  <AppLayout>
    <div class="w-full p-6">
      <Breadcrumbs :items="breadcrumbs" />
      <!-- page content -->
    </div>
  </AppLayout>
</template>
```

**Rules:**
- ✅ Import `Breadcrumbs` from `@/components/Breadcrumbs.vue` directly in the page
- ✅ Use `route()` Ziggy helper for all hrefs — never hardcode strings like `'/admin/dashboard'`
- ✅ `<AppLayout>` takes **no** `:breadcrumbs` prop — breadcrumbs go inside the page div
- ✅ `const breadcrumbs = [...]` — plain const, no `BreadcrumbItem[]` type annotation needed
- ❌ Do NOT use `import type { BreadcrumbItem } from '@/types'` for breadcrumbs
- ❌ Do NOT use `<AppLayout :breadcrumbs="breadcrumbItems">` — that old pattern is wrong
- ❌ Student-facing pages (`Student/Dashboard.vue`, `Student/AccountOverview.vue`) do **not** have breadcrumbs

### Forms — Always Use `useForm()`

```vue
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  field: value,
})

// Submit
form.post(route('resource.store'))
form.put(route('resource.update', id))

// Error display
<p v-if="form.errors.field">{{ form.errors.field }}</p>

// Loading state
<button :disabled="form.processing">Submit</button>
```

**Never use** `reactive()` + `router.post()` for forms — errors are silently lost.

### Route Names

Routes use Ziggy (`ziggy-js`). Always use `route()` helper:

```ts
route('admin.dashboard')
route('students.index')
route('users.index')         // ← admin student archive (NOT admin.users.index)
route('notifications.index') // ← admin notifications (NOT admin.notifications.index)
route('student-fees.show', userId)
route('fees.index')
```

> ⚠️ **Critical:** `Route::resource('users', ...)` inside `->prefix('admin')` does **NOT** auto-prefix route names. Names are `users.index`, `users.create`, etc. — not `admin.users.*`. Only explicitly `.name()`d routes get the admin prefix (e.g. `admin.dashboard`, `admin.users.deactivate`).

**Full named route reference:**

```
dashboard                     → /dashboard (general auth)
student.dashboard             → /student/dashboard
student.account               → /student/account
admin.dashboard               → /admin/dashboard
admin.users.deactivate        → POST /admin/users/{user}/deactivate
admin.users.reactivate        → POST /admin/users/{user}/reactivate
accounting.dashboard          → /accounting/dashboard
users.{index|create|show|edit|store|update|destroy}  → /admin/users/*
notifications.{index|create|show|edit|...}           → /admin/notifications/*
students.index                → /students
students.show                 → /students/{student}
students.payments.store       → POST /students/{student}/payments
students.advance-workflow     → POST /students/{student}/advance-workflow
student-fees.index            → /student-fees
student-fees.show             → /student-fees/{user}
student-fees.edit             → /student-fees/{user}/edit
fees.{index|create|show|...}  → /fees/*
transactions.index            → /student/transactions
accounting.transactions.index → /accounting/transactions
approvals.{index|show}        → /approvals/*
notifications.dismiss         → POST /notifications/{notification}/dismiss
account.pay-now               → POST /student/account/pay-now
profile.edit                  → /settings/profile
```

### Component Library

UI primitives are in `resources/js/components/ui/` — shadcn/reka-ui based:

```ts
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
```

Icons from `lucide-vue-next`:
```ts
import { Users, FileText, CheckCircle2, AlertCircle } from 'lucide-vue-next'
```

Shared app components:
- `Breadcrumbs.vue` — page navigation trail
- `PaymentTermsBreakdown.vue` — payment schedule display
- `NotificationPreview.vue` — notification preview sidebar
- `useDataFormatting.ts` — `formatCurrency()`, `formatDate()`, status configs

### Styling

Tailwind CSS v4. Use utility classes only — no custom CSS unless absolutely necessary. Scoped `<style>` blocks are acceptable for `<tr>` hover states and status badge patterns.

Currency display uses Philippine Peso `₱` via `Intl.NumberFormat('en-PH', { currency: 'PHP' })`. Always use `formatCurrency()` from `useDataFormatting` composable rather than raw formatting.

---

## Database

**Driver:** SQLite (default, see `.env`)  
**ORM:** Eloquent with SoftDeletes on Student, User models

### Key Models & Relationships

```
User
  ├── role: UserRoleEnum (admin | accounting | student)
  ├── admin_type: string (super | manager | operator) — admin only
  ├── hasOne Student (via user_id)
  ├── hasOne Account
  └── hasMany Transaction

Student
  ├── student_id: string (CCDI ID e.g. "2024-0001")
  ├── student_number: string (auto-generated "STU-xxx")
  ├── enrollment_status: enum (pending | active | suspended | graduated)
  ├── uses SoftDeletes
  ├── hasMany Payment
  ├── hasMany StudentPaymentTerm
  ├── morphMany WorkflowInstance
  └── hasMany StudentAssessment (via user_id)

StudentAssessment
  └── hasMany StudentPaymentTerm

StudentPaymentTerm
  ├── term_name, term_order, percentage
  ├── amount, balance (balance = unpaid portion)
  ├── due_date, status, paid_date
  └── carryover support

Notification
  ├── target_role: string (student | admin | accounting | all)
  ├── start_date, end_date
  └── is_active: boolean

Workflow → WorkflowInstance → WorkflowApproval
  └── workflowable: polymorphic (Student, etc.)
```

### Naming Conventions

- Student name is split: `first_name`, `last_name`, `middle_initial` (separate columns)
- Display as: `{{ student.last_name }}, {{ student.first_name }}`
- `student.name` and `student.full_name` are accessor aliases — prefer explicit `first_name`/`last_name` in forms
- Balance source of truth: `StudentPaymentTerm.balance` (sum of all terms) — not `transactions`

---

## Known Issues & Gotchas

### Dead Files
`Students/Show.vue` and `Students/View.vue` are never rendered by any controller. `StudentController::show()` renders `Students/StudentProfile`. Do not edit `Show.vue` or `View.vue`.

### Route Prefix ≠ Route Name Prefix
```php
// This registers route name 'users.index', NOT 'admin.users.index'
Route::middleware([...])->prefix('admin')->group(function () {
    Route::resource('users', AdminController::class);
});
```

### `auth.user.role` is an Enum, not a plain string
In Vue, `auth.user.role` returns the enum value string (`'admin'`, `'accounting'`, `'student'`).  
There is **no** `'super_admin'` role — that concept is `admin_type === 'super'` on an admin user.

### `route('dashboard')` vs Role Dashboards
`route('dashboard')` is the **generic** post-login redirect, not necessarily visible to students. Student pages must link to `route('student.dashboard')`. Admin pages link to `route('admin.dashboard')`.

### WorkflowInstance `activeWorkflow` Prop
`StudentController::show()` passes `activeWorkflow` to `Students/StudentProfile` but the component currently ignores it. This prop is available and should be used to display workflow status.

---

## Page-by-Page Responsibility Matrix

| Page | Role | Breadcrumb Root |
|------|------|-----------------|
| `Admin/Dashboard` | admin | `admin.dashboard` |
| `Admin/Notifications/*` | admin | `admin.dashboard` |
| `Admin/Users/*` | admin | `admin.dashboard` |
| `Students/*` | admin | `dashboard` |
| `StudentFees/*` | admin, accounting | `dashboard` |
| `Fees/*` | admin, accounting | `dashboard` |
| `Accounting/*` | accounting | `accounting.dashboard` |
| `Transactions/*` | accounting, student | role-dependent |
| `Student/Dashboard` | student | no breadcrumbs |
| `Student/AccountOverview` | student | no breadcrumbs |

---

## Common Tasks

### Adding a new admin page

1. Create controller method returning `Inertia::render('Admin/MyPage', [...])`
2. Add route in `routes/web.php` inside the admin middleware group
3. Create `resources/js/pages/Admin/MyPage.vue` following the breadcrumb pattern
4. Add sidebar nav item in `AppSidebar.vue` with `roles: ['admin']`

### Adding a form page

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'

const form = useForm({ field: '' })
const submit = () => form.post(route('resource.store'))

const breadcrumbs = [
  { title: 'Dashboard', href: route('admin.dashboard') },
  { title: 'Resource', href: route('resource.index') },
  { title: 'Create', href: route('resource.create') },
]
</script>

<template>
  <AppLayout>
    <div class="w-full p-6">
      <Breadcrumbs :items="breadcrumbs" />
      <form @submit.prevent="submit">
        <p v-if="form.errors.field" class="text-red-600 text-sm">{{ form.errors.field }}</p>
        <button :disabled="form.processing">Submit</button>
      </form>
    </div>
  </AppLayout>
</template>
```

### Checking user role in Vue

```ts
import { usePage } from '@inertiajs/vue3'
const page = usePage()
const userRole = page.props.auth.user.role  // 'admin' | 'accounting' | 'student'
const isAdmin = userRole === 'admin'
```

---

## File Naming Reference

```
app/
  Http/Controllers/           PascalCase, suffix Controller
  Http/Middleware/            PascalCase
  Models/                     PascalCase singular
  Enums/                      PascalCase, suffix Enum
  Services/                   PascalCase, suffix Service

resources/js/
  pages/Section/Page.vue      PascalCase, matches Inertia::render() path
  components/MyComponent.vue  PascalCase
  composables/useMyThing.ts   camelCase, prefix use
  layouts/AppLayout.vue       PascalCase

database/
  migrations/                 snake_case with timestamp prefix
  seeders/                    PascalCase, suffix Seeder
```