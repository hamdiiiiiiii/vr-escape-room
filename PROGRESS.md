# VR Escape Room – Member 1 Progress Log

This file is updated after every phase. Each entry follows the standard Phase Report format.

---

## Phase 0 Report – Review and Plan

**Status:** DONE

**What was built:**
- Initial project review and architecture planning.
- Installed Laravel Breeze (Blade stack), ran default migrations, and built Vite assets.
- Initialized Git repository on `main`, ensured `.env`, `vendor/`, and `*.sqlite` are ignored, and branched to `member1-web`.
- Updated `.env` and `.env.example` with `SESSION_DRIVER=file`, `CACHE_STORE=file`, and `QUEUE_CONNECTION=sync`.

**Files created/changed:**
- `composer.json`, `composer.lock`
- `.env`, `.env.example`
- `.gitignore`
- Breeze scaffolding files (views, controllers, routes, tests)
- `PROGRESS.md`

**Result of my own testing:**
- Breeze auth scaffolding confirmed working (25 tests passed).

**Problems found:**
- Breeze was not initially installed. Resolved via Option A (Breeze Blade stack installed).

**Ready for next phase:** YES

---

## Phase 1 Report – Roles and Seed Data

**Status:** DONE

**What was built:**
- Added portable `role` column (`string`, default `'student'`) to the `users` table via migration.
- Added helper methods `isInstructor(): bool` and `isStudent(): bool` to the `User` model, and added `'role'` to `$fillable`.
- Updated `DatabaseSeeder` to seed 1 instructor (`instructor@test.com`) and 5 students (`student1@test.com` through `student5@test.com`), all with password `'password'`, `role` assigned, and `email_verified_at` timestamped.
- Added automated unit test (`tests/Unit/UserTest.php`) and feature test (`tests/Feature/SeedDataTest.php`).

**Files created/changed:**
- `database/migrations/2026_11_07_181119_add_role_to_users_table.php` (created)
- `app/Models/User.php` (changed)
- `database/seeders/DatabaseSeeder.php` (changed)
- `tests/Unit/UserTest.php` (created)
- `tests/Feature/SeedDataTest.php` (created)
- `PROGRESS.md` (updated)

**How to test:**
1. Run `php artisan test --filter=SeedDataTest` (or full suite `php artisan test`).
2. Run `php artisan db:seed` to ensure local database has the accounts.
3. Start the dev server: `php artisan serve`.
4. Open `http://localhost:8000/login`.
5. Test instructor account: email `instructor@test.com`, password `password`. Verify login succeeds.
6. Log out, then test student accounts: email `student1@test.com` through `student5@test.com`, password `password`. Verify login succeeds for each.

**Result of my own testing:**
- Automated test suite passed: **29 passed (114 assertions)**, 0 failures.
- All 6 accounts exist in SQLite test database and have verified emails.
- `isInstructor()` returns `true` for instructor and `false` for student; `isStudent()` returns `true` for student and `false` for instructor.
- All 6 accounts authenticate successfully through the `/login` endpoint with password `password`.

**Problems found:** none

**Decisions I made that you should know about:**
- Initialized git repository locally with default branch `main`, verified `.gitignore` excludes `.env`, `/vendor`, and `*.sqlite`, made the initial commit on `main`, and checked out `member1-web`.
- Set `SESSION_DRIVER=file`, `CACHE_STORE=file`, and `QUEUE_CONNECTION=sync` in both `.env` and `.env.example` as instructed.
- Used portable migration syntax (`$table->string('role')->default('student')->after('email')`) for clean MySQL/SQLite cross-compatibility.
- Used `User::updateOrCreate` in `DatabaseSeeder` so seeds are idempotent and safe to re-run.

**Questions for the team (Member 2 / Member 5):** none

**Ready for next phase:** YES

---

## Phase 2 Part A Report – Login Flow, Role Guard, and Registration Removal

**Status:** DONE

**What was built:**
- Product name updated to **"BugHunt VR"** (`APP_NAME="BugHunt VR"`) in `.env` and `.env.example`.
- Configured role-based login redirect: students land on `student.dashboard`, instructors land on `instructor.dashboard`.
- Kept `/dashboard` route (named `dashboard`) redirecting to the user's role dashboard to maintain compatibility with Breeze and existing links.
- Created `EnsureRole` middleware (`app/Http/Middleware/EnsureRole.php`) and registered the `role` alias in `bootstrap/app.php`. Enforces HTTP 403 Forbidden on unauthorized role access.
- Removed public registration: removed GET/POST `/register` routes from `routes/auth.php` and removed the registration link from `resources/views/welcome.blade.php`.
- Verified wrong credentials behavior: confirmed invalid password returns a validation error on `email` (`trans('auth.failed')`) and redirects back.
- Created placeholder dashboard views for student and instructor (`resources/views/student/dashboard.blade.php` and `resources/views/instructor/dashboard.blade.php`).
- Updated registration tests to verify that `/register` returns 404, added tests for login redirects per role, wrong password validation error, and added comprehensive role guard tests.

**Files created/changed:**
- `.env` and `.env.example` (changed: `APP_NAME="BugHunt VR"`)
- `app/Http/Middleware/EnsureRole.php` (created)
- `bootstrap/app.php` (changed: registered `role` middleware alias)
- `routes/auth.php` (changed: removed registration routes)
- `routes/web.php` (changed: role redirect on `/dashboard`, `student.dashboard`, `instructor.dashboard`)
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (changed: redirect by role)
- `resources/views/welcome.blade.php` (changed: removed register link)
- `resources/views/student/dashboard.blade.php` (created)
- `resources/views/instructor/dashboard.blade.php` (created)
- `tests/Feature/Auth/AuthenticationTest.php` (changed: added role redirect and wrong password error tests)
- `tests/Feature/Auth/RegistrationTest.php` (changed: asserts 404 for registration)
- `tests/Feature/RoleGuardTest.php` (created: tests 403 role enforcement)
- `PROGRESS.md` (updated)

**How to test:**
1. Run automated tests: `php artisan test` (all 38 tests should pass).
2. Start dev server: `php artisan serve`.
3. Test wrong password:
   - Go to `http://localhost:8000/login`.
   - Submit `student1@test.com` with `wrong-password`.
   - Confirm page reloads with error message: *"These credentials do not match our records."*
4. Test student login:
   - Submit `student1@test.com` with `password`.
   - Confirm redirect to `http://localhost:8000/student/dashboard` with "Student Dashboard".
5. Test student role guard:
   - While logged in as student, navigate to `http://localhost:8000/instructor/dashboard`.
   - Confirm HTTP 403 Forbidden is returned.
6. Test instructor login:
   - Log out, then log in as `instructor@test.com` with `password`.
   - Confirm redirect to `http://localhost:8000/instructor/dashboard` with "Instructor Dashboard".
7. Test instructor role guard:
   - While logged in as instructor, navigate to `http://localhost:8000/student/dashboard`.
   - Confirm HTTP 403 Forbidden is returned.
8. Test registration removal:
   - Navigate to `http://localhost:8000/register`.
   - Confirm HTTP 404 Not Found is returned.

**Result of my own testing:**
- Automated test suite: **38 passed (132 assertions)**, 0 failures.
- Student login redirects to `student.dashboard`.
- Instructor login redirects to `instructor.dashboard`.
- `/dashboard` redirects students to `student.dashboard` and instructors to `instructor.dashboard`.
- Student accessing instructor routes receives 403.
- Instructor accessing student routes receives 403.
- Guest accessing protected routes redirects to `/login`.
- Submitting invalid password leaves user as guest and returns validation error on `email`.
- Registration routes return 404.

**Problems found:** none

**Decisions I made that you should know about:**
- Updated `AuthenticatedSessionController@store` to redirect directly to the role dashboard via `intended()`, while keeping `/dashboard` as a smart router so existing Breeze redirects continue to work.
- Configured `EnsureRole` to support variadic parameters (e.g. `role:student` or `role:admin,instructor`) and return `403` via `abort(Response::HTTP_FORBIDDEN)`.
- Kept `RegistrationTest.php` active by converting tests to assert 404 Not Found, confirming registration routes are fully disabled without deleting test files.

**Questions for the team (Member 2 / Member 5):** none

**Ready for next phase:** YES (ready for Phase 2 Part B: shared layout, navigation, and README_TEAM.md)

---

## Phase 2 Part B Report – Shared Layout, Navigation, Design Components, and Team README

**Status:** DONE

**What was built:**
- Built reusable Blade design components:
  - `<x-primary-button>`: Primary indigo (`#4F46E5` / `bg-indigo-600`), rounded-xl, soft shadow.
  - `<x-secondary-button>`: Clean white card button with border-gray-300, rounded-xl, hover states.
  - `<x-card>`: White card with rounded corners (`rounded-2xl`), soft shadow (`shadow-sm`), and border.
  - `<x-page-heading>`: Clean typography with title, subtitle, and responsive actions slot.
  - `<x-status-badge>`: Standardized status pills (waiting = yellow, in progress = blue with pulse indicator, finished = green).
  - `<x-application-logo>`: Playful VR headset and bug icon with "BugHunt VR" branding.
- Shared top navigation bar:
  - Desktop: "BugHunt VR" logo on the left, role dashboard link in the middle, user name + role pill + quick Log out button on the right.
  - Mobile (phone width): Collapses into an Alpine.js hamburger button with slide-down drawer showing user info, role badge, navigation links, and full-width Log out button.
- Shared layouts (`app.blade.php`, `guest.blade.php`, and `welcome.blade.php`):
  - Consistent light gray background (`bg-slate-50`), friendly rounded style for kids' learning, readable typography, and no dark mode.
- Student & Instructor placeholder pages:
  - Both updated using `<x-app-layout>`, `<x-page-heading>`, `<x-card>`, and `<x-status-badge>`.
  - Display "Member 2 fills this in" placeholder notice, so pages look complete, clean, and structured.
- Placeholder report page:
  - Created `resources/views/reports/show.blade.php` and registered `reports.show` route (`/reports/{id}`) in `routes/web.php` with authentication.
- Team documentation (`README_TEAM.md`):
  - Setup guide (< 15 min clone to run).
  - Test accounts table.
  - "How to add a new page" guide for Member 2.
  - Complete route map and component usage reference.
- Compiled Vite production assets (`npm run build`).

**Files created/changed:**
- `resources/views/components/primary-button.blade.php` (changed)
- `resources/views/components/secondary-button.blade.php` (changed)
- `resources/views/components/card.blade.php` (created)
- `resources/views/components/page-heading.blade.php` (created)
- `resources/views/components/status-badge.blade.php` (created)
- `resources/views/components/application-logo.blade.php` (changed)
- `resources/views/components/text-input.blade.php` (changed)
- `resources/views/layouts/app.blade.php` (changed)
- `resources/views/layouts/guest.blade.php` (changed)
- `resources/views/layouts/navigation.blade.php` (changed)
- `resources/views/student/dashboard.blade.php` (changed)
- `resources/views/instructor/dashboard.blade.php` (changed)
- `resources/views/reports/show.blade.php` (created)
- `resources/views/welcome.blade.php` (changed)
- `routes/web.php` (changed: added `reports.show` route)
- `tests/Feature/RoleGuardTest.php` (changed: added report route test)
- `README_TEAM.md` (created)
- `PROGRESS.md` (updated)

**How to test:**
1. Run automated test suite: `php artisan test` (all 39 tests pass).
2. Start dev server: `php artisan serve`.
3. Open `http://localhost:8000/login` in a browser.
4. Test Student View:
   - Log in as `student1@test.com` (password: `password`).
   - Check navigation: "BugHunt VR" logo, "Student Dashboard" link, user name pill with "STUDENT", and "Log Out" button.
   - Verify student dashboard displays page heading, status badges (waiting, in progress, finished), and card with "Member 2 fills this in".
   - Test phone view: Resize browser to < 640px width (e.g. 375px mobile emulator). Confirm top nav collapses into hamburger button, tap hamburger to open drawer, verify navigation and mobile logout button work.
5. Test Instructor View:
   - Log out, log in as `instructor@test.com` (password: `password`).
   - Check navigation shows "Instructor Dashboard" and "INSTRUCTOR" pill.
   - Verify instructor dashboard displays metrics and placeholder card.
6. Test Report Placeholder:
   - Navigate to `http://localhost:8000/reports/1`.
   - Verify report page displays session #1, finished badge, and "Member 2 fills this in".

**Result of my own testing:**
- Automated test suite passed: **39 passed (135 assertions)**, 0 failures.
- Vite build completed cleanly in 3.06s (`npm run build`).
- Student, Instructor, and Report pages render with shared layout, primary indigo accent, rounded-2xl cards, and clear spacing.
- Mobile drawer toggles cleanly using Alpine.js at phone widths without layout overflow.
- No dark mode styling conflicts.

**Problems found:** none

**Decisions I made that you should know about:**
- Created dedicated `<x-card>`, `<x-page-heading>`, and `<x-status-badge>` components to make it effortless for Member 2 to build consistent pages.
- Status badge automatically maps aliases: `waiting` (yellow), `in_progress` / `in progress` (blue + pulse), and `finished` / `completed` (green).
- Set up `README_TEAM.md` with explicit component examples and a 3-step guide for Member 2 to add new pages.

**Questions for the team (Member 2 / Member 5):**
- Member 2: The shared layout and component suite are ready for your instructor dashboard and report pages!

**Ready for next phase:** YES (ready for Phase 3: Student dashboard with fake data)
