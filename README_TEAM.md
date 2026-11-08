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

Tests run on an in-memory SQLite database automatically (`phpunit.xml`). All 46+ tests should pass.

---

## 8. Services & Backend Handoff (For Member 5)

To keep the web frontend decoupled from database table development, all room and session data is isolated behind two stub service classes in `app/Services`:

```
app/Services/
  ├── RoomService.php     # Provides rooms and room lookup
  └── SessionService.php  # Provides student session history and session creation
```

### 1. `RoomService`
- **Method:** `getRooms(): array`
  Returns an array of available escape rooms.
  ```php
  [
      [
          'id' => 1,
          'name' => 'The Boundary Maze',
          'description' => '...',
          'time_limit_minutes' => 15,
          'concept_tag' => 'Boundary Testing',
      ],
      // ...
  ]
  ```
- **Method:** `findRoom(int|string $id): ?array`
  Finds a single room by its identifier.

### 2. `SessionService`
- **Method:** `getPastSessions(User $user): array`
  Returns the past sessions for a given user.
  ```php
  [
      [
          'id' => 101,
          'room_id' => 1,
          'room_name' => 'The Boundary Maze',
          'date' => 'Nov 05, 2026',
          'status' => 'finished',    // 'waiting' | 'in_progress' | 'finished'
          'score' => 92,             // integer or null
      ],
      // ...
  ]
  ```

### How Member 5 Will Replace Them
When the real `rooms`, `sessions`, and `reports` database tables or external API are ready:
1. **DO NOT** edit the Controllers or Blade views.
2. Edit only `app/Services/RoomService.php` and `app/Services/SessionService.php`.
3. Replace the stub arrays inside each method with real Eloquent queries (e.g. `Room::all()`, `Session::where('user_id', $user->id)->get()`) or API HTTP calls (`Http::get(...)`).
4. Ensure the returned array keys match the format documented above.

### 3. Session start methods (Phase 4A)

The stub keeps sessions in the **Laravel cache** (file driver), one entry per session id (`stub_session:{id}`) with the owner's `user_id` inside. No database table. Session ids start at 1001.

- **`startSession(User $user, int $roomId): array`**
  Creates a session with a unique zero-padded 6-digit code (unique among active sessions), status `waiting`, `expires_at = now + 10 minutes`.
  ```json
  { "session_id": 1001, "code": "042913", "status": "waiting", "expires_at": "2026-11-08T02:10:00+02:00" }
  ```
- **`getSession(int|string $id): ?array`**
  Returns the full session (`id`, `user_id`, `room_id`, `room_name`, `code`, `status`, `created_at`, `expires_at`) or `null`. If `expires_at` has passed and status is still `waiting`, status becomes `expired`.
- **`getStatus(int|string $id): ?array`**
  Used by the status polling endpoint. **This is the exact JSON the web page expects from Member 5's API** (`GET /sessions/{id}/status` on our side):
  ```json
  { "session_id": 1001, "status": "waiting", "expires_at": "2026-11-08T02:10:00+02:00" }
  ```
  `status` is one of `waiting`, `in_progress`, `finished`, `expired`. `expires_at` is an ISO 8601 date.
  Note: `expired` is not stored by us as a real VR state. Whoever owns the data must either return `expired` themselves or let us derive it from `expires_at` (as the stub does).
- **`simulateJoin(int|string $id): ?array`** (**DEV ONLY**)
  Fakes the VR game accepting the code (`waiting` -> `in_progress`). Remove it together with the route below.

### New routes (all `auth` + `role:student`; a student gets 403 on someone else's session)

| Route name | Method + URL | Purpose |
|---|---|---|
| `student.start-room.create` | `POST /rooms/{id}/start` | Create the session, redirect to the code screen |
| `student.start-room` | `GET /rooms/{id}/start` | Old link, only redirects to the dashboard |
| `student.session.show` | `GET /sessions/{id}` | Code screen (placeholder until Phase 4B) |
| `student.session.status` | `GET /sessions/{id}/status` | JSON status for polling |
| `dev.session.simulate-join` | `POST /dev/sessions/{id}/simulate-join` | **DEV ONLY**, 404 unless `APP_ENV=local` |

### What Member 5 must remove/replace
1. Replace the bodies of `startSession`, `getSession`, `getStatus` in `SessionService` with real database/API calls (keep the return shapes above).
2. Delete `SessionService::simulateJoin()`, `StudentController::simulateJoin()` and the `dev.session.simulate-join` route.
3. The stub cache keys (`stub_session*`) and `getPastSessions()` fake data go away. Note: sessions created via `startSession` do **not** yet appear in the "My past sessions" list, which still shows fixed fake data.

---

## 9. Integration Notes for Member 5

When integrating the real MySQL database schema, migrations, VR game API, and session models, follow this checklist:

### 1. Database Configuration (`.env`)
Switch from SQLite to MySQL in your `.env` file:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vr_escape_room
DB_USERNAME=root
DB_PASSWORD=
```

### 2. Migration & Seed Command
To execute the migrations and populate initial users:

```bash
php artisan migrate --seed
```

### 3. Which Stubs to Replace
Do **not** modify controllers or views directly. Replace only the internal implementation inside `app/Services/RoomService.php` and `app/Services/SessionService.php`:

#### A. `RoomService` (`app/Services/RoomService.php`)
- `getRooms(): array`
  - **Return shape:** Array of associative arrays, each containing:
    ```php
    [
        'id' => (int) 1,
        'name' => (string) 'The Boundary Maze',
        'description' => (string) '...',
        'time_limit_minutes' => (int) 15,
        'concept_tag' => (string) 'Boundary Testing',
    ]
    ```
- `findRoom(int|string $id): ?array`
  - **Return shape:** Single room array above or `null` if not found.

#### B. `SessionService` (`app/Services/SessionService.php`)
- `getPastSessions(User $user): array`
  - **Return shape:** Array of past sessions for `$user`:
    ```php
    [
        'id' => (int) 101,
        'room_id' => (int) 1,
        'room_name' => (string) 'The Boundary Maze',
        'date' => (string) 'Nov 05, 2026',
        'status' => (string) 'finished', // 'waiting' | 'in_progress' | 'finished' | 'expired'
        'score' => (int|null) 92,        // integer (0-100) or null if not yet finished
    ]
    ```
- `startSession(User $user, int $roomId): array`
  - **Return shape:**
    ```php
    [
        'session_id' => (int) 1001,
        'code' => (string) '042913',      // 6-digit zero-padded string
        'status' => (string) 'waiting',
        'expires_at' => (string) '2026-11-08T02:10:00+02:00', // ISO 8601 string (+10 minutes)
    ]
    ```
- `getSession(int|string $id): ?array`
  - **Return shape:** Full session array or `null`. Must contain: `id`, `user_id`, `room_id`, `room_name`, `code`, `status`, `created_at`, `expires_at`.
- `getStatus(int|string $id): ?array`
  - **Return shape:** Exact status JSON payload for polling:
    ```json
    {
        "session_id": 1,
        "status": "waiting",
        "expires_at": "2026-11-08T02:10:00+02:00"
    }
    ```
    Allowed `status` values: `"waiting" | "in_progress" | "finished" | "expired"`.

### 4. What to Remove When the Real API / DB is Connected
1. **Dev route:** Remove `Route::post('/dev/sessions/{id}/simulate-join', ...)` from `routes/web.php`.
2. **Controller method:** Remove `simulateJoin()` in `app/Http/Controllers/StudentController.php`.
3. **Service method:** Remove `simulateJoin()` in `app/Services/SessionService.php`.
4. **Dev UI button:** Remove the `@if (app()->environment('local')) ... @endif` block in `resources/views/student/session.blade.php`.
5. **Stub cache code:** Remove cache storage keys (`stub_session:*`, `stub_session_ids`, `stub_session_counter`) from `SessionService.php`.

### 5. Important Note on "My Past Sessions"
The student dashboard's "My past sessions" table currently displays fixed mock data returned by `SessionService::getPastSessions()`. Sessions created dynamically via `startSession()` are stored in the temporary stub cache and are not yet linked to the past sessions list. Member 5 should query the real database `sessions` table where `user_id = $user->id` and sort by `created_at desc`.



