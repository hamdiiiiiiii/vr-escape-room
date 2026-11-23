<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\InsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentInsightsApiController extends Controller
{
    /**
     * Weak points of one student (used by the future AI personalisation).
     * A student can read their own; an instructor can read assigned students.
     */
    public function weakPoints(Request $request, int $id, InsightsService $insights): JsonResponse
    {
        $student = User::find($id);
        abort_if($student === null || ! $student->isStudent(), 404);

        $me = $request->user();
        $allowed = $me->id === $student->id
            || ($me->isInstructor() && (int) $student->instructor_id === (int) $me->id);
        abort_unless($allowed, 403);

        return response()->json($insights->weakPointsPayload($student));
    }
}
