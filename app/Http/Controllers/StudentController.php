<?php

namespace App\Http\Controllers;

use App\Services\RoomService;
use App\Services\SessionService;
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
     * Display the placeholder start room screen (completed in Phase 4).
     */
    public function startRoom(string|int $id, RoomService $roomService): View
    {
        $room = $roomService->findRoom($id);

        if (! $room) {
            abort(404, 'Room not found.');
        }

        return view('student.start-room', [
            'room' => $room,
        ]);
    }
}
