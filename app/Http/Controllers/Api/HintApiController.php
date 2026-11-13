<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Models\GeneratedPuzzle;
use App\Services\HintTutorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/sessions/{id}/hint  (session token, like the results endpoint)
 * Returns a nudge for one puzzle. Unity can call it when the player asks for help.
 * It does not change the score; hint counting stays in the game, as before.
 */
class HintApiController extends Controller
{
    public function show(Request $request, int $id, HintTutorService $tutor): JsonResponse
    {
        $session = GameSession::with('room')->find($id);
        abort_if($session === null, 404, 'Session not found.');

        $user = $request->user();
        abort_unless($session->user_id === $user->id && $user->currentAccessToken()?->name === 'session-'.$session->id, 403, 'This token is not for this session.');
        abort_unless($session->status === 'in_progress', 409, 'This session is not in progress.');

        $data = $request->validate([
            'puzzleId' => ['required', 'string', 'max:64'],
            'level' => ['sometimes', 'integer', 'min:0', 'max:1'],
            'wrongAnswers' => ['sometimes', 'nullable', 'array', 'max:3'],
            'wrongAnswers.*' => [function ($attribute, $value, $fail) {
                if ((! is_scalar($value) && ! is_array($value)) || mb_strlen(json_encode($value)) > 200) {
                    $fail('Each wrong answer must be short text, a number or a small object.');
                }
            }],
        ]);

        $puzzle = collect($session->room->config['puzzles'] ?? [])->firstWhere('id', $data['puzzleId']);

        if ($puzzle === null) {
            $generated = GeneratedPuzzle::where('status', GeneratedPuzzle::APPROVED)->get()
                ->first(fn ($g) => ($g->content['id'] ?? null) === $data['puzzleId']);
            $puzzle = $generated?->content;
        }

        abort_if($puzzle === null, 404, 'Puzzle not found.');

        return response()->json($tutor->hint($puzzle, $data['wrongAnswers'] ?? [], $data['level'] ?? 0));
    }
}
