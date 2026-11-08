<?php

namespace App\Http\Controllers;

use App\Services\RoomService;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display the student dashboard with rooms and past sessions.
     */
    public function dashboard(Request $request, RoomService $roomService, SessionService $sessionService): View
    {
        $rooms = $roomService->getRooms();
        $sessions = $sessionService->getPastSessions($request->user());

        return view('student.dashboard', [
            'student' => $request->user(),
            'rooms' => $rooms,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Old GET link. Creating a session must be a POST, so this only redirects
     * back to the dashboard (keeps old links from breaking).
     */
    public function startRoom(): RedirectResponse
    {
        return redirect()->route('student.dashboard');
    }

    /**
     * Create a session for the chosen room and show its code screen.
     */
    public function createSession(string|int $id, Request $request, RoomService $roomService, SessionService $sessionService): RedirectResponse
    {
        if (! $roomService->findRoom($id)) {
            abort(404, 'Room not found.');
        }

        $created = $sessionService->startSession($request->user(), (int) $id);

        return redirect()->route('student.session.show', $created['session_id']);
    }

    /**
     * Code screen (placeholder view, Phase 4 part B builds the real one).
     */
    public function showSession(string|int $id, Request $request, SessionService $sessionService): View
    {
        $session = $this->findOwnedSession($id, $request, $sessionService);

        return view('student.session', ['session' => $session]);
    }

    /**
     * JSON status used by the page to poll every 3 seconds.
     */
    public function sessionStatus(string|int $id, Request $request, SessionService $sessionService): JsonResponse
    {
        $this->findOwnedSession($id, $request, $sessionService);

        return response()->json($sessionService->getStatus($id));
    }

    /**
     * DEV ONLY, remove when Member 5's API is connected.
     * Fakes "the student typed the code in VR": waiting -> in_progress.
     * Only works when APP_ENV=local, otherwise 404.
     */


    /**
     * Load a session: 404 if it does not exist, 403 if it belongs to someone else.
     */
    private function findOwnedSession(string|int $id, Request $request, SessionService $sessionService): array
    {
        $session = $sessionService->getSession($id);

        abort_if($session === null, 404, 'Session not found.');
        abort_if($session['user_id'] !== $request->user()->id, 403, 'This is not your session.');

        return $session;
    }
}
