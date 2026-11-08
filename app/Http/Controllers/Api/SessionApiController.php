<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Models\PuzzleResult;
use App\Models\Report;
use App\Services\ScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionApiController extends Controller
{
    public function __construct(private ScoringService $scoring)
    {
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $session = GameSession::with('room', 'user')
            ->where('code', $data['code'])
            ->where('status', 'waiting')
            ->first();

        if (! $session) {
            return response()->json(['message' => 'Invalid code.'], 404);
        }

        if (now()->greaterThanOrEqualTo($session->expires_at)) {
            return response()->json(['message' => 'Code expired.'], 410);
        }

        // Single use: move to in_progress and clear the code
        $session->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'code' => null,
        ]);

        $token = $session->user->createToken('session-'.$session->id)->plainTextToken;

        $room = $session->room->config ?? [];
        $room['roomId'] = $session->room->id;
        $room['name'] = $session->room->name;
        $room['timeLimitSeconds'] = $session->room->time_limit_seconds;

        return response()->json([
            'sessionId' => $session->id,
            'token' => $token,
            'room' => $room,
        ]);
    }

    public function storeResult(Request $request, int $id): JsonResponse
    {
        $session = $this->ownedSession($request, $id);

        $data = $request->validate([
            'puzzleId' => ['required', 'string', 'max:64'],
            'conceptTag' => ['required', 'string', 'max:100'],
            'solved' => ['required', 'boolean'],
            'attempts' => ['required', 'integer', 'min:1', 'max:3'],
            'timeSeconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'hintsUsed' => ['required', 'integer', 'min:0', 'max:2'],
        ]);

        if ($session->status !== 'in_progress') {
            return response()->json(['message' => 'Session is not in progress.'], 409);
        }

        $score = $this->scoring->puzzleScore(
            (bool) $data['solved'],
            (int) $data['attempts'],
            (int) $data['hintsUsed']
        );

        // updateOrCreate makes retries safe: the same puzzle is never stored twice
        PuzzleResult::updateOrCreate(
            ['game_session_id' => $session->id, 'puzzle_id' => $data['puzzleId']],
            [
                'concept_tag' => $data['conceptTag'],
                'solved' => (bool) $data['solved'],
                'attempts' => $data['attempts'],
                'time_seconds' => $data['timeSeconds'],
                'hints_used' => $data['hintsUsed'],
                'score' => $score,
            ]
        );

        return response()->json([
            'puzzleId' => $data['puzzleId'],
            'score' => $score,
        ], 201);
    }

    public function endSession(Request $request, int $id): JsonResponse
    {
        $session = $this->ownedSession($request, $id);

        $data = $request->validate([
            'reason' => ['required', 'in:escaped,timed_out'],
        ]);

        if ($session->status !== 'in_progress') {
            return response()->json(['message' => 'Session is not in progress.'], 409);
        }

        $results = PuzzleResult::where('game_session_id', $session->id)->get()->keyBy('puzzle_id');

        $rows = $results->map(fn ($r) => [
            'concept_tag' => $r->concept_tag,
            'score' => $r->score,
        ])->values()->all();

        // Puzzles the player never reached count as 0
        foreach ($session->room->config['puzzles'] ?? [] as $puzzle) {
            if (! $results->has($puzzle['id'])) {
                $rows[] = [
                    'concept_tag' => $puzzle['conceptTag'] ?? 'unknown',
                    'score' => 0,
                ];
            }
        }

        $summary = $this->scoring->summarize($rows);
        $totalTime = $session->started_at
            ? max(0, (int) $session->started_at->diffInSeconds(now()))
            : 0;

        DB::transaction(function () use ($session, $data, $summary, $totalTime) {
            $session->update([
                'status' => 'finished',
                'end_reason' => $data['reason'],
                'ended_at' => now(),
            ]);

            Report::updateOrCreate(
                ['game_session_id' => $session->id],
                [
                    'total_time_seconds' => $totalTime,
                    'session_score' => $summary['session_score'],
                    'level' => $summary['level'],
                    'concept_scores' => $summary['concept_scores'],
                    'ai_analysis' => null,
                ]
            );
        });

        // The token was only for this session, so it is no longer needed
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'sessionId' => $session->id,
            'status' => 'finished',
            'endReason' => $data['reason'],
            'totalTimeSeconds' => $totalTime,
            'sessionScore' => $summary['session_score'],
            'level' => $summary['level'],
            'conceptScores' => $summary['concept_scores'],
        ]);
    }

    /**
     * 404 if the session does not exist, 403 if it is not this token's session.
     */
    private function ownedSession(Request $request, int $id): GameSession
    {
        $session = GameSession::with('room')->find($id);

        abort_if($session === null, 404, 'Session not found.');

        $user = $request->user();
        $sameUser = $session->user_id === $user->id;
        $sameToken = $user->currentAccessToken()?->name === 'session-'.$session->id;

        abort_unless($sameUser && $sameToken, 403, 'This token is not for this session.');

        return $session;
    }
}