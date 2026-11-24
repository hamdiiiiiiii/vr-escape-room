<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionEvent extends Model
{
    /** Event types the game (or the server) may log. */
    public const TYPES = [
        'room_entered', 'puzzle_started', 'answer_submitted', 'answer_checked',
        'hint_requested', 'door_opened', 'session_ended',
    ];

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    /** Server-side helper. Logging must never break the request, so failures are swallowed. */
    public static function record(GameSession $session, string $type, ?string $puzzleId = null, ?array $payload = null): void
    {
        try {
            static::create([
                'game_session_id' => $session->id,
                'type' => $type,
                'puzzle_id' => $puzzleId,
                'payload' => $payload,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
