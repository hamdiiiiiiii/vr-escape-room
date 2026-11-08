<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\Room;
use App\Models\User;

class SessionService
{
    /** How long a waiting session (and its 6-digit code) stays valid. */
    public const CODE_LIFETIME_MINUTES = 10;

    /**
     * @return array{session_id: int, code: string, status: string, expires_at: string}
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
            'code' => $session->code,
            'status' => 'waiting',
            'expires_at' => $session->expires_at->toIso8601String(),
        ];
    }

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
            'created_at' => $session->created_at->toIso8601String(),
            'expires_at' => $session->expires_at->toIso8601String(),
        ];
    }

    /**
     * @return array{session_id: int, status: string, expires_at: string}|null
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
            'expires_at' => $session->expires_at->toIso8601String(),
        ];
    }

    /**
     * DEV ONLY, will be removed. Pretend the VR game accepted the code.
     */
    

    /**
     * @return array<int, array{id: int, room_id: int, room_name: string, date: string, status: string, score: int|null}>
     */
    public function getPastSessions(User $user): array
    {
        return GameSession::with(['room', 'report'])
            ->where('user_id', $user->id)
            ->get()
            ->sortByDesc(fn (GameSession $s) => $s->started_at ?? $s->created_at)
            ->map(fn (GameSession $s) => [
                'id' => $s->id,
                'room_id' => $s->room_id,
                'room_name' => $s->room?->name ?? 'Unknown room',
                'date' => ($s->started_at ?? $s->created_at)->format('M d, Y'),
                'status' => $this->effectiveStatus($s),
                'score' => $s->report?->session_score,
            ])
            ->values()
            ->all();
    }

    /** "waiting" becomes "expired" once expires_at has passed (not stored). */
    private function effectiveStatus(GameSession $session): string
    {
        if ($session->status === 'waiting' && now()->greaterThanOrEqualTo($session->expires_at)) {
            return 'expired';
        }

        return $session->status;
    }

    /** 6-digit, zero padded code that no active session is using. */
    private function generateUniqueCode(): string
    {
        $activeCodes = GameSession::whereNotNull('code')
            ->where(function ($q) {
                $q->where('status', 'in_progress')
                    ->orWhere(fn ($q2) => $q2->where('status', 'waiting')->where('expires_at', '>', now()));
            })
            ->pluck('code')
            ->all();

        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (in_array($code, $activeCodes, true));

        return $code;
    }
}