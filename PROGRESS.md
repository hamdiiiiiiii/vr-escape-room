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
