# BugHunt VR – Team Development Guide

Welcome to the **BugHunt VR** web application (Member 1 & 2 workspace). This guide explains how to set up the project locally in under 15 minutes, log in with test accounts, and build new pages on the shared architecture.

---

## 1. Quick Setup (< 15 Minutes)

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & npm
- Git

### Step-by-Step Setup

```bash
# 1. Clone repository & enter directory
git checkout member1-web

# 2. Install PHP dependencies
composer install

# 3. Environment configuration
copy .env.example .env
php artisan key:generate

# 4. Local database setup (SQLite for local testing)
# Note: database/database.sqlite is git-ignored
type nul > database\database.sqlite
php artisan migrate
php artisan db:seed

# 5. Build frontend assets
npm install
npm run build

# 6. Start development server
php artisan serve
```

Visit the app at: `http://localhost:8000`

---

## 2. Test Accounts

All accounts are pre-seeded with email verification and the same simple password:

| Role | Email | Password | Landing Page |
|---|---|---|---|
| **Instructor** | `instructor@test.com` | `password` | `/instructor/dashboard` |
| **Student 1** | `student1@test.com` | `password` | `/student/dashboard` |
| **Student 2** | `student2@test.com` | `password` | `/student/dashboard` |
| **Student 3** | `student3@test.com` | `password` | `/student/dashboard` |
| **Student 4** | `student4@test.com` | `password` | `/student/dashboard` |
| **Student 5** | `student5@test.com` | `password` | `/student/dashboard` |

> Public user registration is disabled. Only pre-created instructor and student accounts can access the application.

---

## 3. Route Map & Role Protection

Routes are guarded by the `EnsureRole` middleware (alias `'role'`). Unauthorized access returns HTTP `403 Forbidden`.

| Route Name | URL | Middleware | Purpose |
|---|---|---|---|
| `login` | `/login` | `guest` | Unified login form |
| `dashboard` | `/dashboard` | `auth, verified` | Smart router that redirects by role |
| `student.dashboard` | `/student/dashboard` | `auth, role:student` | Student room launcher and past scores |
| `instructor.dashboard` | `/instructor/dashboard` | `auth, role:instructor` | Classroom monitoring & analytics (Member 2) |
| `reports.show` | `/reports/{id}` | `auth` | Session performance report placeholder |
| `profile.edit` | `/profile` | `auth` | User profile settings |

---

## 4. How to Add a New Page (Guide for Member 2)

Follow these steps to add any new instructor or student view:

### Step 1: Add Route in `routes/web.php`
Guard the route with the appropriate role middleware:

```php
// For Instructor pages:
Route::middleware(['auth', 'role:instructor'])->group(function () {
    Route::get('/instructor/analytics', [InstructorController::class, 'analytics'])
        ->name('instructor.analytics');
});

// For Student pages:
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student/rooms', [StudentController::class, 'rooms'])
        ->name('student.rooms');
});
```

### Step 2: Create the Blade View
Create `resources/views/instructor/analytics.blade.php` using the shared layout `<x-app-layout>`:

```blade
<x-app-layout>
    <!-- Page Heading -->
    <x-page-heading
        title="Classroom Analytics"
        description="Review student bug-hunting success rate and testing metrics.">
        <x-slot name="actions">
            <x-primary-button type="button">Export Data</x-primary-button>
        </x-slot>
    </x-page-heading>

    <!-- Main Content in a Card -->
    <x-card title="Error Distribution" description="Breakdown of software bugs solved.">
        <p class="text-sm text-gray-600">Your analytics charts go here.</p>
    </x-card>
</x-app-layout>
```

### Step 3: Add Navigation Item in `resources/views/layouts/navigation.blade.php`
Add the menu link inside the desktop navigation and mobile drawer:

```blade
<a href="{{ route('instructor.analytics') }}"
   class="px-3.5 py-2 rounded-xl text-sm font-semibold transition {{ request()->routeIs('instructor.analytics') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
    {{ __('Analytics') }}
</a>
```

---

## 5. Shared Design Components

We use a friendly, rounded aesthetic tailored for educational gaming (no dark mode):

### Primary & Secondary Buttons
```blade
<x-primary-button type="submit">Start Room</x-primary-button>
<x-secondary-button type="button">Cancel</x-secondary-button>
```

### Card Component
White card with `rounded-2xl`, soft shadow, and padding:
```blade
<x-card title="Challenge Details" description="Boundary value analysis puzzle">
    <p>Card body content goes here...</p>
</x-card>
```

### Page Heading Component
```blade
<x-page-heading title="My Results" description="Summary of past sessions">
    <x-slot name="actions">
        <x-primary-button>New Run</x-primary-button>
    </x-slot>
</x-page-heading>
```

### Status Badge
Automatically formats colors and pulse indicators:
```blade
<!-- Waiting = Yellow -->
<x-status-badge status="waiting" />

<!-- In Progress = Blue with subtle pulse -->
<x-status-badge status="in_progress" />

<!-- Finished = Green -->
<x-status-badge status="finished" />
```

---

## 6. Design System Reference

- **Primary Accent**: `#4F46E5` (`bg-indigo-600`, `text-indigo-600`)
- **Background**: `bg-slate-50`
- **Cards**: `bg-white rounded-2xl shadow-sm border border-gray-100`
- **Typography**: Clean, readable sans-serif (Figtree)
- **Mobile First**: All pages include a responsive mobile top navigation with toggle drawer (`sm:hidden`).

---

## 7. Running Automated Tests

```bash
php artisan test
```

Tests run on an in-memory SQLite database automatically (`phpunit.xml`). All 38+ tests should pass.

