<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\User;

class SessionService
{
    /**
     * How long a waiting session and its six-digit code stay valid.
     */
    public const CODE_LIFETIME_MINUTES = 10;

    /**
     * Create a database-backed session for a student.
     *
     * @return array{
     *     session_id: int,
     *     code: string,
     *     status: string,
     *     expires_at: string
     * }
     */
    public function startSession(User $user, int $roomId): array
    {
        $session = GameSession::create([
            'user_id' => $user->id,
            'room_id' => $roomId,
            'code' => $this->generateUniqueCode(),
            'status' => 'waiting',
            'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES),
        ]);

        return [
            'session_id' => $session->id,
            'code' => (string) $session->code,
            'status' => $session->status,
            'expires_at' => $session->expires_at->toIso8601String(),
        ];
    }

    /**
     * Get a session and its room information.
     */
    public function getSession(int|string $id): ?array
    {
        $session = GameSession::with('room')->find((int) $id);

        if (! $session) {
            return null;
        }

        return [
            'id' => $session->id,
            'user_id' => $session->user_id,
            'room_id' => $session->room_id,
            'room_name' => $session->room?->name ?? 'Unknown room',
            'code' => (string) $session->code,
            'status' => $this->effectiveStatus($session),
            'created_at' => $session->created_at?->toIso8601String(),
            'expires_at' => $session->expires_at?->toIso8601String(),
        ];
    }

    /**
     * Get the current status of a session.
     *
     * @return array{
     *     session_id: int,
     *     status: string,
     *     expires_at: string|null
     * }|null
     */
    public function getStatus(int|string $id): ?array
    {
        $session = GameSession::find((int) $id);

        if (! $session) {
            return null;
        }

        return [
            'session_id' => $session->id,
            'status' => $this->effectiveStatus($session),
            'expires_at' => $session->expires_at?->toIso8601String(),
        ];
    }

    /**
     * Get the student's previous sessions.
     *
     * @return array<int, array{
     *     id: int,
     *     room_id: int,
     *     room_name: string,
     *     date: string,
     *     status: string,
     *     score: int|float|null
     * }>
     */
    public function getPastSessions(User $user): array
    {
        return GameSession::with(['room', 'report'])
            ->where('user_id', $user->id)
            ->get()
            ->sortByDesc(
                fn (GameSession $session) =>
                    $session->started_at ?? $session->created_at
            )
            ->map(fn (GameSession $session) => [
                'id' => $session->id,
                'room_id' => $session->room_id,
                'room_name' => $session->room?->name ?? 'Unknown room',
                'date' => (
                    $session->started_at ?? $session->created_at
                )?->format('M d, Y'),
                'status' => $this->effectiveStatus($session),
                'score' => $session->report?->session_score,
            ])
            ->values()
            ->all();
    }

    /**
     * A waiting session is considered expired when its expiry time passes.
     *
     * This does not change the stored database status.
     */
    private function effectiveStatus(GameSession $session): string
    {
        if (
            $session->status === 'waiting'
            && $session->expires_at !== null
            && now()->greaterThanOrEqualTo($session->expires_at)
        ) {
            return 'expired';
        }

        return $session->status;
    }

    /**
     * Generate a six-digit code that is not currently in use by an active
     * or unexpired waiting session.
     */
    private function generateUniqueCode(): string
    {
        $activeCodes = GameSession::query()
            ->whereNotNull('code')
            ->where(function ($query) {
                $query->where('status', 'in_progress')
                    ->orWhere(function ($waitingQuery) {
                        $waitingQuery
                            ->where('status', 'waiting')
                            ->where('expires_at', '>', now());
                    });
            })
            ->pluck('code')
            ->map(fn ($code) => (string) $code)
            ->all();

        do {
            $code = str_pad(
                (string) random_int(0, 999999),
                6,
                '0',
                STR_PAD_LEFT
            );
        } while (in_array($code, $activeCodes, true));

        return $code;
    }
}
