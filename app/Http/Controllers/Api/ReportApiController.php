<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends Controller
{
    /**
     * Return a completed session's report.
     *
     * Access is allowed to:
     * - The student who owns the session.
     * - The instructor assigned to that student.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $session = GameSession::with([
            'room',
            'report',
            'puzzleResults',
            'user',
        ])->find($id);

        abort_if(
            $session === null,
            404,
            'Session not found.'
        );

        $user = $request->user();

        $isOwner = (int) $session->user_id === (int) $user->id;

        $isTheirInstructor = $user->isInstructor()
            && (int) $session->user?->instructor_id === (int) $user->id;

        abort_unless(
            $isOwner || $isTheirInstructor,
            403,
            'Not allowed.'
        );

        abort_if(
            $session->status !== 'finished' || $session->report === null,
            404,
            'Report not available.'
        );

        $report = $session->report;
        $results = $session->puzzleResults;

        $puzzlesTotal = count(
            $session->room?->config['puzzles'] ?? []
        );

        $puzzlesSolved = $results
            ->filter(fn ($result) => (bool) $result->solved)
            ->count();

        $solvedWithoutHelp = $results
            ->filter(
                fn ($result) =>
                    (bool) $result->solved
                    && (int) $result->hints_used === 0
            )
            ->count();

        return response()->json([
            'sessionId' => $session->id,
            'room' => $session->room?->name,
            'status' => $session->status,
            'endReason' => $session->end_reason,

            'totalTimeSeconds' => $report->total_time_seconds,
            'puzzlesSolved' => $puzzlesSolved,
            'puzzlesTotal' => $puzzlesTotal,
            'solvedWithoutHelp' => $solvedWithoutHelp,

            'sessionScore' => $report->session_score,
            'level' => $report->level,

            'puzzles' => $results->map(fn ($result) => [
                'puzzleId' => $result->puzzle_id,
                'conceptTag' => $result->concept_tag,
                'solved' => (bool) $result->solved,
                'attempts' => $result->attempts,
                'timeSeconds' => $result->time_seconds,
                'hintsUsed' => $result->hints_used,
                'score' => $result->score,
            ])->values(),

            'concepts' => collect($report->concept_scores ?? [])
                ->map(fn ($score, $tag) => [
                    'conceptTag' => $tag,
                    'score' => $score,
                ])
                ->values(),

            'ai' => $report->ai_analysis,
        ]);
    }
}
