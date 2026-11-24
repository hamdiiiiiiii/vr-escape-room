<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Services\AvatarService;
use App\Services\SquadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * For Unity.
 *   GET /api/avatar/options          the catalogue (no login, nothing personal in it)
 *   GET /api/sessions/{id}/avatar    the player's own look and public name (session token)
 *   GET /api/sessions/{id}/squad     the squad (1 to 4 players) with every player's public name and look (session token)
 * The same blocks also come back in the verify-code answer, so the game normally needs no extra request.
 */
class AvatarApiController extends Controller
{
    public function options(AvatarService $avatars): JsonResponse
    {
        return response()->json($avatars->catalog());
    }

    public function mine(Request $request, int $id, AvatarService $avatars): JsonResponse
    {
        $this->ownedSession($request, $id);

        return response()->json($avatars->playerPayload($request->user()));
    }

    public function squad(Request $request, int $id, SquadService $squads): JsonResponse
    {
        $this->ownedSession($request, $id);

        return response()->json($squads->payload($request->user()));
    }

    private function ownedSession(Request $request, int $id): GameSession
    {
        $session = GameSession::find($id);
        abort_if($session === null, 404, 'Session not found.');

        $user = $request->user();
        abort_unless(
            $session->user_id === $user->id && $user->currentAccessToken()?->name === 'session-'.$session->id,
            403,
            'This token is not for this session.'
        );

        return $session;
    }
}
