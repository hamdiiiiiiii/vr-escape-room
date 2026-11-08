<?php

// STUB: replace with Member 5's API/database later.

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SessionService
{
    /** How long a waiting session (and its 6-digit code) stays valid. */
    public const CODE_LIFETIME_MINUTES = 10;

    private const CACHE_IDS_KEY = 'stub_session_ids';
    private const CACHE_COUNTER_KEY = 'stub_session_counter';
    private const CACHE_PREFIX = 'stub_session:';

    /**
     * Create a new "waiting" session with a unique 6-digit code.
     *
     * Stored in the Laravel cache (no database table).
     *
     * @return array{session_id: int, code: string, status: string, expires_at: string}
     */
    public function startSession(User $user, int $roomId): array
    {
        $room = (new RoomService())->findRoom($roomId);

        // Start above the fake past-session ids (101..401) so ids never clash.
        $id = $this->nextId();

        $session = [
            'id' => $id,
            'user_id' => $user->id,
            'room_id' => $roomId,
            'room_name' => $room['name'] ?? 'Unknown room',
            'code' => $this->generateUniqueCode(),
            'status' => 'waiting',
            'created_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES)->toIso8601String(),
        ];

        $this->saveSession($session);

        $ids = Cache::get(self::CACHE_IDS_KEY, []);
        $ids[] = $id;
        Cache::forever(self::CACHE_IDS_KEY, $ids);

        return [
            'session_id' => $id,
            'code' => $session['code'],
            'status' => $session['status'],
            'expires_at' => $session['expires_at'],
        ];
    }

    /**
     * Get one session (or null). If it is still "waiting" but expires_at has
     * passed, its status becomes "expired".
     *
     * @return array{id: int, user_id: int, room_id: int, room_name: string, code: string, status: string, created_at: string, expires_at: string}|null
     */
    public function getSession(int|string $id): ?array
    {
        $session = Cache::get(self::CACHE_PREFIX.(int) $id);

        if (! $session) {
            return null;
        }

        if ($session['status'] === 'waiting' && now()->greaterThanOrEqualTo($session['expires_at'])) {
            $session['status'] = 'expired';
            $this->saveSession($session);
        }

        return $session;
    }

    /**
     * Status payload used by the polling endpoint.
     *
     * @return array{session_id: int, status: string, expires_at: string}|null
     */
    public function getStatus(int|string $id): ?array
    {
        $session = $this->getSession($id);

        if (! $session) {
            return null;
        }

        return [
            'session_id' => $session['id'],
            'status' => $session['status'],
            'expires_at' => $session['expires_at'],
        ];
    }

    /**
     * DEV ONLY, remove when Member 5's API is connected.
     * Pretend the VR game accepted the code: waiting -> in_progress.
     */
    public function simulateJoin(int|string $id): ?array
    {
        $session = $this->getSession($id);

        if ($session && $session['status'] === 'waiting') {
            $session['status'] = 'in_progress';
            $this->saveSession($session);
        }

        return $this->getStatus($id);
    }

    private function saveSession(array $session): void
    {
        Cache::forever(self::CACHE_PREFIX.$session['id'], $session);
    }

    private function nextId(): int
    {
        $last = Cache::get(self::CACHE_COUNTER_KEY, 1000);
        $next = $last + 1;
        Cache::forever(self::CACHE_COUNTER_KEY, $next);

        return $next;
    }

    /**
     * 6-digit, zero padded code that no active (waiting/in_progress, not expired)
     * session is using.
     */
    private function generateUniqueCode(): string
    {
        $activeCodes = [];

        foreach (Cache::get(self::CACHE_IDS_KEY, []) as $id) {
            $session = $this->getSession($id);

            if ($session && in_array($session['status'], ['waiting', 'in_progress'], true)) {
                $activeCodes[] = $session['code'];
            }
        }

        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (in_array($code, $activeCodes, true));

        return $code;
    }

    /**
     * Get past escape room sessions for a specific student.
     *
     * @param User $user
     * @return array<int, array{id: int, room_id: int, room_name: string, date: string, status: string, score: int|null}>
     */
    public function getPastSessions(User $user): array
    {
        // One student (student5@test.com or modulo 0) has an empty session history.
        if ($user->email === 'student5@test.com' || ($user->id > 0 && $user->id % 5 === 0)) {
            return [];
        }

        // Student 2 has 1 finished and 1 waiting session
        if ($user->email === 'student2@test.com' || $user->id % 5 === 2) {
            return [
                [
                    'id' => 201,
                    'room_id' => 2,
                    'room_name' => 'Equivalence Chamber',
                    'date' => 'Nov 06, 2026',
                    'status' => 'finished',
                    'score' => 88,
                ],
                [
                    'id' => 202,
                    'room_id' => 1,
                    'room_name' => 'The Boundary Maze',
                    'date' => 'Nov 07, 2026',
                    'status' => 'waiting',
                    'score' => null,
                ],
            ];
        }

        // Student 3 has only a waiting session
        if ($user->email === 'student3@test.com' || $user->id % 5 === 3) {
            return [
                [
                    'id' => 301,
                    'room_id' => 1,
                    'room_name' => 'The Boundary Maze',
                    'date' => 'Nov 04, 2026',
                    'status' => 'waiting',
                    'score' => null,
                ],
            ];
        }

        // Student 4 has a perfect score on Assertion Vault
        if ($user->email === 'student4@test.com' || $user->id % 5 === 4) {
            return [
                [
                    'id' => 401,
                    'room_id' => 3,
                    'room_name' => 'The Assertion Vault',
                    'date' => 'Nov 07, 2026',
                    'status' => 'finished',
                    'score' => 100,
                ],
            ];
        }

        // Default student (e.g. Student 1 / modulo 1) has 2 finished and 1 in_progress session
        return [
            [
                'id' => 101,
                'room_id' => 1,
                'room_name' => 'The Boundary Maze',
                'date' => 'Nov 05, 2026',
                'status' => 'finished',
                'score' => 92,
            ],
            [
                'id' => 102,
                'room_id' => 2,
                'room_name' => 'Equivalence Chamber',
                'date' => 'Nov 06, 2026',
                'status' => 'finished',
                'score' => 85,
            ],
            [
                'id' => 103,
                'room_id' => 3,
                'room_name' => 'The Assertion Vault',
                'date' => 'Nov 07, 2026',
                'status' => 'in_progress',
                'score' => null,
            ],
        ];
    }
}
