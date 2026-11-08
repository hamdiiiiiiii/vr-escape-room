<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $session = GameSession::with(['room', 'report', 'puzzleResults', 'user'])->find($id);

        abort_if($session === null, 404, 'Session not found.');

        $user = $request->user();
                $isOwner = (int) $session->user_id === (int) $user->id;
        $isTheirInstructor = $user->isInstructor()
            && (int) $session->user?->instructor_id === (int) $user->id;
        abort_unless($isOwner || $isTheirInstructor, 403, 'Not allowed.');
        abort_if($session->status !== 'finished' || $session->report === null, 404, 'Report not available.');

        $report = $session->report;
        $results = $session->puzzleResults;

        return response()->json([
            'sessionId' => $session->id,
            'room' => $session->room?->name,
            'status' => $session->status,
            'endReason' => $session->end_reason,
            'totalTimeSeconds' => $report->total_time_seconds,
            'puzzlesSolved' => $results->where('solved', true)->count(),
            'puzzlesTotal' => count($session->room?->config['puzzles'] ?? []),
            'solvedWithoutHelp' => $results->where('solved', true)->where('hints_used', 0)->count(),
            'sessionScore' => $report->session_score,
            'level' => $report->level,
            'puzzles' => $results->map(fn ($r) => [
                'puzzleId' => $r->puzzle_id,
                'conceptTag' => $r->concept_tag,
                'solved' => (bool) $r->solved,
                'attempts' => $r->attempts,
                'timeSeconds' => $r->time_seconds,
                'hintsUsed' => $r->hints_used,
                'score' => $r->score,
            ])->values(),
            'concepts' => collect($report->concept_scores ?? [])
                ->map(fn ($score, $tag) => ['conceptTag' => $tag, 'score' => $score])
                ->values(),
            'ai' => $report->ai_analysis,
        ]);
    }
}