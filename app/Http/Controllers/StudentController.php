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
     * Display the student dashboard with available rooms and past sessions.
     */
    public function dashboard(
        Request $request,
        RoomService $roomService,
        SessionService $sessionService
    ): View {
        $student = $request->user();

        $rooms = $roomService->getRooms();
        $sessions = $sessionService->getPastSessions($student);

        return view('student.dashboard', [
            'student' => $student,
            'rooms' => $rooms,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Keep old GET links working without creating a session.
     * Session creation must happen through POST.
     */
    public function startRoom(): RedirectResponse
    {
        return redirect()->route('student.dashboard');
    }

    /**
     * Create a database-backed session for the selected room.
     */
    public function createSession(
        string|int $id,
        Request $request,
        RoomService $roomService,
        SessionService $sessionService
    ): RedirectResponse {
        $room = $roomService->findRoom($id);

        abort_if($room === null, 404, 'Room not found.');

        $created = $sessionService->startSession(
            $request->user(),
            (int) $id
        );

        return redirect()->route(
            'student.session.show',
            $created['session_id']
        );
    }

    /**
     * Display the session code screen.
     */
    public function showSession(
        string|int $id,
        Request $request,
        SessionService $sessionService
    ): View {
        $session = $this->findOwnedSession(
            $id,
            $request,
            $sessionService
        );

        return view('student.session', [
            'session' => $session,
        ]);
    }

    /**
     * Return the session status for frontend polling.
     */
    public function sessionStatus(
        string|int $id,
        Request $request,
        SessionService $sessionService
    ): JsonResponse {
        $this->findOwnedSession($id, $request, $sessionService);

        return response()->json(
            $sessionService->getStatus($id)
        );
    }

    /**
     * Load a session and verify that it belongs to the authenticated student.
     *
     * Returns 404 if the session does not exist and 403 if it belongs
     * to another user.
     */
    private function findOwnedSession(
        string|int $id,
        Request $request,
        SessionService $sessionService
    ): array {
        $session = $sessionService->getSession($id);

        abort_if(
            $session === null,
            404,
            'Session not found.'
        );

        abort_if(
            (int) $session['user_id'] !== (int) $request->user()->id,
            403,
            'This is not your session.'
        );

        return $session;
    }
}
