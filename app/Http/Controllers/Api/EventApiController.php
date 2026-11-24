<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Models\SessionEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * POST /api/sessions/{id}/events  (session token)
 * The game sends what happened in the room (up to 50 events per call). Stored for the instructor timeline.
 */
class EventApiController extends Controller
{
    public function store(Request $request, int $id): JsonResponse
    {
        $session = GameSession::find($id);
        abort_if($session === null, 404, 'Session not found.');

        $user = $request->user();
        abort_unless($session->user_id === $user->id && $user->currentAccessToken()?->name === 'session-'.$session->id, 403, 'This token is not for this session.');
        abort_unless($session->status === 'in_progress', 409, 'This session is not in progress.');

        $data = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', 'string', Rule::in(SessionEvent::TYPES)],
            'events.*.puzzleId' => ['sometimes', 'nullable', 'string', 'max:64'],
            'events.*.occurredAt' => ['sometimes', 'nullable', 'date'],
            'events.*.payload' => ['sometimes', 'nullable', 'array', function ($attribute, $value, $fail) {
                if (mb_strlen(json_encode($value)) > 500) {
                    $fail('An event payload is too large (500 characters at most).');
                }
            }],
        ]);

        $now = now();
        $rows = collect($data['events'])->map(function ($e) use ($session, $now) {
            $at = ! empty($e['occurredAt']) ? Carbon::parse($e['occurredAt']) : $now;

            return [
                'game_session_id' => $session->id,
                'type' => $e['type'],
                'puzzle_id' => $e['puzzleId'] ?? null,
                'payload' => isset($e['payload']) ? json_encode($e['payload']) : null,
                // a wrong clock on the headset must not put events in the future
                'occurred_at' => $at->greaterThan($now) ? $now : $at,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        SessionEvent::insert($rows);

        return response()->json(['stored' => count($rows)], 201);
    }
}
