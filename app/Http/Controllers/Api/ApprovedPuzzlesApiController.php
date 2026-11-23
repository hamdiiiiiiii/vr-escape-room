<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneratedPuzzle;
use App\Models\User;
use App\Services\LearnerModelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Approved AI puzzles for one student, in the content-pack format, plus the
 * existing puzzles the recommender suggests. Used by Unity later.
 * Allowed for the student themself or their instructor.
 */
class ApprovedPuzzlesApiController extends Controller
{
    public function show(Request $request, int $id, LearnerModelService $learner): JsonResponse
    {
        $student = User::find($id);
        abort_if($student === null || ! $student->isStudent(), 404, 'Student not found.');

        $me = $request->user();
        abort_unless($me->id === $student->id || ($me->isInstructor() && $student->instructor_id === $me->id), 403);

        $puzzles = GeneratedPuzzle::where('status', GeneratedPuzzle::APPROVED)
            ->where(fn ($q) => $q->where('student_id', $student->id)
                ->orWhere(fn ($q) => $q->whereNull('student_id')->where('requested_by', $student->instructor_id)))
            ->orderBy('id')
            ->get();

        return response()->json([
            'studentId' => $student->id,
            'generatedPuzzles' => $puzzles->map(fn ($p) => $p->content + ['generatedPuzzleId' => $p->id, 'difficulty' => $p->difficulty])->all(),
            // Fallback: the normal puzzles the recommender picks.
            'recommendedExisting' => $learner->recommend($student)['next_puzzles'],
        ]);
    }
}
